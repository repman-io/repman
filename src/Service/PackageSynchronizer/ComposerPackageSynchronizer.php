<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\PackageSynchronizer;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Package\Abandoned;
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
use Composer\Package\CompletePackage;
use Composer\Package\Link as ComposerLink;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryInterface;
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
        string $gitlabUrl
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
            $io = $this->createIO($package);
            /** @var RepositoryInterface $repository */
            $repository = current(RepositoryFactory::defaultRepos($io, $this->createConfig($package, $io)));
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
                $json['packages'][$p->getPrettyName()][$p->getPrettyVersion()] = $this->packageNormalizer->normalize($p);
                if (Comparator::greaterThan($p->getVersion(), $latest->getVersion()) && $p->getStability() === Version::STABILITY_STABLE) {
                    $latest = $p;
                }
            }

            /** @var string|null $name */
            $name = $latest->getPrettyName();

            if ($name === null) {
                throw new \RuntimeException('Missing package name in latest version. Revision: '.$latest->getDistReference());
            }

            if (preg_match(Package::NAME_PATTERN, $name, $matches) !== 1) {
                throw new \RuntimeException("Package name {$name} is invalid");
            }

            if (!$package->isSynchronized() && $this->packageRepository->packageExist($name, $package->organizationId())) {
                throw new \RuntimeException("Package {$name} already exists. Package name must be unique within organization.");
            }

            $versions = [];
            foreach ($packages as $p) {
                if ($p->getDistUrl() !== null) {
                    $versions[] = [
                        'organizationAlias' => $package->organizationAlias(),
                        'packageName' => $p->getPrettyName(),
                        'prettyVersion' => $p->getPrettyVersion(),
                        'version' => $p->getVersion(),
                        'ref' => $p->getDistReference() ?? '',
                        'distType' => $p->getDistType(),
                        'distUrl' => $p->getDistUrl(),
                        'authHeaders' => $this->getAuthHeaders($package),
                        'releaseDate' => \DateTimeImmutable::createFromMutable($p->getReleaseDate() ?? new \DateTime()),
                        'stability' => $p->getStability(),
                    ];
                }
            }

            usort($versions, fn ($item1, $item2) => $item2['releaseDate'] <=> $item1['releaseDate']);

            $encounteredVersions = [];
            $encounteredLinks = [];
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

                $this->distStorage->download(
                    $version['distUrl'],
                    $dist,
                    $this->getAuthHeaders($package)
                );

                if ($latest->getVersion() === $version['version']) {
                    $this->readmeExtractor->extractReadme($package, $dist);

                    // Set the version links
                    foreach (['requires', 'devRequires', 'provides', 'replaces', 'conflicts'] as $type) {
                        $functionName = 'get'.$type;
                        if (method_exists($latest, $functionName)) {
                            /** @var ComposerLink $link */
                            foreach ($latest->{$functionName}() as $link) {
                                $package->addLink(new Link(Uuid::uuid4(), $package, $type, $link->getTarget(), $link->getPrettyConstraint()));
                                $encounteredLinks[$type.'-'.$link->getTarget()] = true;
                            }
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
                        $this->distStorage->size($dist),
                        $version['releaseDate'],
                        $version['stability']
                    )
                );

                $encounteredVersions[$version['prettyVersion']] = true;
            }

            $package->syncSuccess(
                $name,
                $latest instanceof CompletePackage ? ($latest->getDescription() ?? 'n/a') : 'n/a',
                $latest->getStability() === Version::STABILITY_STABLE ? $latest->getPrettyVersion() : 'no stable release',
                $encounteredVersions,
                $encounteredLinks,
                \DateTimeImmutable::createFromMutable($latest->getReleaseDate() ?? new \DateTime()),
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

    private function createIO(Package $package): BufferIO
    {
        $io = new BufferIO('', OutputInterface::VERBOSITY_VERY_VERBOSE);

        if ($package->type() === 'github-oauth') {
            $io->setAuthentication('github.com', $this->accessToken($package), 'x-oauth-basic');
        }

        if ($package->type() === 'gitlab-oauth') {
            $io->setAuthentication($this->getGitlabUrl(), $this->accessToken($package), 'oauth2');
        }

        if ($package->type() === 'bitbucket-oauth') {
            $io->setAuthentication('bitbucket.org', 'x-token-auth', $this->accessToken($package));
        }

        return $io;
    }

    private function accessToken(Package $package): string
    {
        return $package->oauthToken()->accessToken($this->tokenRefresher);
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
