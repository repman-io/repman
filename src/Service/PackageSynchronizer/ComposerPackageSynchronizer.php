<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\PackageSynchronizer;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Package\Link;
use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\PackageNormalizer;
use Buddy\Repman\Service\PackageSynchronizer;
use Buddy\Repman\Service\ReadmeExtractor;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher;
use Composer\Config;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\VcsRepository;
use Composer\Semver\Comparator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Output\OutputInterface;

final class ComposerPackageSynchronizer implements PackageSynchronizer
{
    private PackageManager $packageManager;
    private PackageNormalizer $packageNormalizer;
    private PackageRepository $packageRepository;
    private Storage $distStorage;
    private ReadmeExtractor $readmeExtractor;
    private UserOAuthTokenRefresher $tokenRefresher;
    private string $gitlabUrl;

    public function __construct(
        PackageManager $packageManager,
        PackageNormalizer $packageNormalizer,
        PackageRepository $packageRepository,
        Storage $distStorage,
        UserOAuthTokenRefresher $tokenRefresher,
        string $gitlabUrl,
    ) {
        $this->packageManager = $packageManager;
        $this->packageNormalizer = $packageNormalizer;
        $this->packageRepository = $packageRepository;
        $this->distStorage = $distStorage;
        $this->tokenRefresher = $tokenRefresher;
        $this->gitlabUrl = $gitlabUrl;
        $this->readmeExtractor = new ReadmeExtractor($this->distStorage);
    }

    public function synchronize(Package $package): void
    {
        try {
            $io = $this->createIO();
            $repository = $this->createRepository($package, $io);
            $this->authenticate($package, $io);
            $json = ['packages' => []];
            $packages = $repository->getPackages();

            usort($packages, static function (PackageInterface $a, PackageInterface $b): int {
                if ($a->getVersion() === $b->getVersion()) {
                    return $a->getReleaseDate() <=> $b->getReleaseDate();
                }

                if ($a->getStability() === Version::STABILITY_STABLE && $b->getStability() !== Version::STABILITY_STABLE) {
                    return -1;
                }

                if ($a->getStability() !== Version::STABILITY_STABLE && $b->getStability() === Version::STABILITY_STABLE) {
                    return 1;
                }

                return Comparator::greaterThan($a->getVersion(), $b->getVersion()) ? 1 : -1;
            });

            if ($packages === []) {
                throw new \RuntimeException('Package not found');
            }

            $latest = current($packages);

            foreach ($packages as $p) {
                // artifacts only have a checksum: the dist is stored under it, and the client builds the
                // download URL from the reference, so without one it asks for ".../<version>/.zip"
                if ($p->getDistReference() === null && $p->getDistSha1Checksum() !== null) {
                    $p->setDistReference($p->getDistSha1Checksum());
                }
                $json['packages'][$p->getPrettyName()][$p->getPrettyVersion()] = $this->packageNormalizer->normalize($p);
                if (Comparator::greaterThan($p->getVersion(), $latest->getVersion()) && $p->getStability() === Version::STABILITY_STABLE) {
                    $latest = $p;
                }
            }

            if ($latest instanceof AliasPackage) {
                $latest = $latest->getAliasOf();
            }
            $name = $latest->getPrettyName();

            if (preg_match(Package::NAME_PATTERN, $name, $matches) !== 1) {
                throw new \RuntimeException("Package name {$name} is invalid");
            }

            if (!$package->isSynchronized() && $this->packageRepository->packageExist($name, $package->organizationId())) {
                throw new \RuntimeException("Package {$name} already exists. Package name must be unique within organization.");
            }

            // plain git, hg and svn repositories have no dist (Composer installs them from source),
            // their versions and links are still recorded, only the archive download is skipped
            $versions = [];
            foreach ($packages as $p) {
                // the default branch alias (9999999-dev) is not a version of its own
                if ($p instanceof AliasPackage) {
                    continue;
                }
                $versions[] = [
                    'organizationAlias' => $package->organizationAlias(),
                    'packageName' => $p->getPrettyName(),
                    'prettyVersion' => $p->getPrettyVersion(),
                    'version' => $p->getVersion(),
                    'ref' => $p->getDistReference() ?? $p->getDistSha1Checksum() ?? $p->getSourceReference() ?? '',
                    'distType' => $p->getDistType() ?? 'zip',
                    // a path dist is a directory on this server, not an archive anybody could download
                    'distUrl' => $p->getDistType() === 'path' ? null : $p->getDistUrl(),
                    'releaseDate' => \DateTimeImmutable::createFromInterface($p->getReleaseDate() ?? new \DateTimeImmutable()),
                    'stability' => $p->getStability(),
                    'defaultBranch' => $p->isDefaultBranch(),
                ];
            }

            usort($versions, fn ($item1, $item2) => $item2['releaseDate'] <=> $item1['releaseDate']);

            $encounteredVersions = [];
            $encounteredLinks = [];
            $sourceLocks = [];
            foreach ($versions as $version) {
                $dist = new Dist(
                    $version['organizationAlias'],
                    $version['packageName'],
                    $version['version'],
                    $version['ref'],
                    $version['distType']
                );

                if (
                    $latest->getVersion() !== $version['version']
                    && $package->keepLastReleases() > 0
                    && count($encounteredVersions) >= $package->keepLastReleases()
                ) {
                    $this->distStorage->remove($dist);
                    $package->removeVersion(new Version(
                        Uuid::uuid4(),
                        $version['prettyVersion'],
                        $version['ref'],
                        0,
                        $version['releaseDate'],
                        $version['stability']
                    ));

                    continue;
                }

                if ($version['distUrl'] !== null) {
                    $this->distStorage->download(
                        $version['distUrl'],
                        $dist,
                        $this->getAuthHeaders($package)
                    );
                }

                if ($latest->getVersion() === $version['version']) {
                    if ($version['distUrl'] !== null) {
                        $this->readmeExtractor->extractReadme($package, $dist);
                    }

                    // Set the version links
                    $links = [
                        'requires' => $latest->getRequires(),
                        'devRequires' => $latest->getDevRequires(),
                        'provides' => $latest->getProvides(),
                        'replaces' => $latest->getReplaces(),
                        'conflicts' => $latest->getConflicts(),
                    ];
                    foreach ($links as $type => $typeLinks) {
                        foreach ($typeLinks as $link) {
                            $package->addLink(new Link(Uuid::uuid4(), $package, $type, $link->getTarget(), $link->getPrettyConstraint()));
                            $encounteredLinks[$type.'-'.$link->getTarget()] = true;
                        }
                    }

                    // suggests are different
                    foreach ($latest->getSuggests() as $linkName => $linkDescription) {
                        $package->addLink(new Link(Uuid::uuid4(), $package, 'suggests', $linkName, $linkDescription));
                        $encounteredLinks['suggests-'.$linkName] = true;
                    }

                    // Is abandoned ?
                    if ($latest instanceof CompletePackage && $latest->isAbandoned()) {
                        $package->setReplacementPackage($latest->getReplacementPackage() ?? '');
                    } else {
                        $package->setReplacementPackage(null);
                    }
                }

                $package->addOrUpdateVersion(
                    new Version(
                        Uuid::uuid4(),
                        $version['prettyVersion'],
                        $version['ref'],
                        $version['distUrl'] !== null ? $this->distStorage->size($dist) : 0,
                        $version['releaseDate'],
                        $version['stability']
                    )
                );

                // the security scan reads composer.lock from the dist, a version installed from source has
                // none: keep the lock of the versions it scans (the latest release, the default branch)
                if ($version['distUrl'] === null && ($latest->getVersion() === $version['version'] || $version['defaultBranch'])
                    && $this->saveSourceLock($repository, $package, $version['packageName'], $version['version'], $version['ref'])) {
                    $sourceLocks[$version['version']] = $version['ref'];
                }

                $encounteredVersions[$version['prettyVersion']] = true;
            }

            $this->packageManager->keepOnlySourceLocks($package->organizationAlias(), $name, $sourceLocks);

            $package->syncSuccess(
                $name,
                $latest instanceof CompletePackage ? ($latest->getDescription() ?? 'n/a') : 'n/a',
                $latest->getStability() === Version::STABILITY_STABLE ? $latest->getPrettyVersion() : 'no stable release',
                $encounteredVersions,
                $encounteredLinks,
                \DateTimeImmutable::createFromInterface($latest->getReleaseDate() ?? new \DateTimeImmutable()),
            );

            $this->packageManager->saveProvider($json, $package->organizationAlias(), $name);
        } catch (\Throwable $exception) {
            $package->syncFailure(sprintf('Error: %s%s',
                $exception->getMessage(),
                isset($io) && strlen($io->getOutput()) > 1 ? "\nLogs:\n".$io->getOutput() : ''
            ));
        }
    }

    /**
     * @return string[]
     */
    private function getAuthHeaders(Package $package): array
    {
        if (!$package->hasOAuthToken()) {
            return [];
        }

        return [sprintf('Authorization: Bearer %s', $this->accessToken($package))];
    }

    private function getGitlabUrl(): string
    {
        $port = (string) \parse_url($this->gitlabUrl, \PHP_URL_PORT);
        if ($port !== '') {
            $port = ':'.$port;
        }

        return \parse_url($this->gitlabUrl, \PHP_URL_HOST).$port;
    }

    private function createIO(): BufferIO
    {
        return new BufferIO('', OutputInterface::VERBOSITY_VERY_VERBOSE);
    }

    /**
     * Must run after the repository is created: that loads the Composer config into the IO, and a global
     * token (COMPOSER_AUTH) would otherwise replace the token of the package.
     */
    private function authenticate(Package $package, IOInterface $io): void
    {
        if ($package->type() === 'github-oauth') {
            $io->setAuthentication('github.com', $this->accessToken($package), 'x-oauth-basic');
        }

        if ($package->type() === 'gitlab-oauth') {
            $io->setAuthentication($this->getGitlabUrl(), $this->accessToken($package), 'oauth2');
        }

        if ($package->type() === 'bitbucket-oauth') {
            $io->setAuthentication('bitbucket.org', 'x-token-auth', $this->accessToken($package));
        }
    }

    private function accessToken(Package $package): string
    {
        return $package->oauthToken()->accessToken($this->tokenRefresher);
    }

    /**
     * Read through the VCS driver, which works on the mirror Composer cloned for this sync and with its
     * credentials: the scan itself may run where neither is available (repman:security:scan-all in cron).
     */
    private function saveSourceLock(RepositoryInterface $repository, Package $package, string $name, string $version, string $ref): bool
    {
        if (!$repository instanceof VcsRepository || $ref === '') {
            return false;
        }

        try {
            $lock = $repository->getDriver()?->getFileContent('composer.lock', $ref);
        } catch (\RuntimeException) {
            // Mercurial and Subversion answer a missing file with an error
            $lock = null;
        }

        if ($lock === null) {
            return false;
        }
        $this->packageManager->saveSourceLock($package->organizationAlias(), $name, $version, $ref, $lock);

        return true;
    }

    private function createRepository(Package $package, IOInterface $io): RepositoryInterface
    {
        $config = $this->createConfig($package, $io);
        $repository = current(RepositoryFactory::defaultRepos($io, $config, RepositoryFactory::manager($io, $config)));
        if (!$repository instanceof RepositoryInterface) {
            throw new \RuntimeException('No repository configured for package');
        }

        return $repository;
    }

    private function createConfig(Package $package, IOInterface $io): Config
    {
        unset(Config::$defaultRepositories['packagist.org']);
        $config = Factory::createConfig($io);
        $config->merge([
            'repositories' => [
                [
                    'type' => strpos($package->type(), '-oauth') !== false ? 'vcs' : $package->type(),
                    'url' => $package->repositoryUrl(),
                ],
            ],
        ]);
        if ($package->type() === 'gitlab-oauth') {
            $config->merge([
                'config' => [
                    'gitlab-domains' => [$this->getGitlabUrl()],
                ],
            ]);
        }

        return $config;
    }
}
