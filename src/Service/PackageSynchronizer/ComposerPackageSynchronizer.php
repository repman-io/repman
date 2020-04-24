<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\PackageSynchronizer;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\PackageNormalizer;
use Buddy\Repman\Service\PackageSynchronizer;
use Composer\Config;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryInterface;
use Composer\Semver\Comparator;
use Symfony\Component\Console\Output\OutputInterface;

final class ComposerPackageSynchronizer implements PackageSynchronizer
{
    private PackageManager $packageManager;
    private PackageNormalizer $packageNormalizer;
    private PackageRepository $packageRepository;
    private Storage $distStorage;

    public function __construct(PackageManager $packageManager, PackageNormalizer $packageNormalizer, PackageRepository $packageRepository, Storage $distStorage)
    {
        $this->packageManager = $packageManager;
        $this->packageNormalizer = $packageNormalizer;
        $this->packageRepository = $packageRepository;
        $this->distStorage = $distStorage;
    }

    public function synchronize(Package $package): void
    {
        $io = $this->createIO($package);

        try {
            /** @var RepositoryInterface $repository */
            $repository = current(RepositoryFactory::defaultRepos($io, $this->createConfig($package, $io)));
            $json = ['packages' => []];
            $packages = $repository->getPackages();

            if ($packages === []) {
                throw new \RuntimeException('Package not found');
            }

            $latest = current($packages);

            foreach ($packages as $p) {
                $json['packages'][$p->getPrettyName()][$p->getPrettyVersion()] = $this->packageNormalizer->normalize($p);
                if (Comparator::greaterThan($p->getVersion(), $latest->getVersion()) && $p->getStability() === 'stable') {
                    $latest = $p;
                }
            }

            $name = $latest->getPrettyName();

            if (preg_match(Package::NAME_PATTERN, $name, $matches) !== 1) {
                throw new \RuntimeException("Package name {$name} is invalid");
            }

            if (!$package->isSynchronized() && $this->packageRepository->packageExist($name, $package->organizationId())) {
                throw new \RuntimeException("Package {$name} already exists. Package name must be unique within organization.");
            }

            foreach ($packages as $p) {
                if ($p->getDistUrl() !== null) {
                    $this->distStorage->download(
                        $p->getDistUrl(),
                        new Dist($package->organizationAlias(), $p->getPrettyName(), $p->getVersion(), $p->getDistReference() ?? $p->getDistSha1Checksum(), $p->getDistType()),
                        $this->getAuthHeaders($package)
                    );
                }
            }

            $package->syncSuccess(
                $name,
                $latest instanceof CompletePackage ? ($latest->getDescription() ?? 'n/a') : 'n/a',
                $latest->getStability() === 'stable' ? $latest->getPrettyVersion() : 'no stable release',
                \DateTimeImmutable::createFromMutable($latest->getReleaseDate() ?? new \DateTime()),
            );

            $this->packageManager->saveProvider($json, $package->organizationAlias(), $name);
        } catch (\Throwable $exception) {
            $package->syncFailure(sprintf('Error: %s%s',
                $exception->getMessage(),
                strlen($io->getOutput()) > 1 ? "\nLogs:\n".$io->getOutput() : ''
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

        return [sprintf('Authorization: Bearer %s', $package->oauthToken())];
    }

    private function createIO(Package $package): BufferIO
    {
        $io = new BufferIO('', OutputInterface::VERBOSITY_VERY_VERBOSE);

        if ($package->type() === 'github-oauth') {
            $io->setAuthentication('github.com', $package->oauthToken(), 'x-oauth-basic');
        }

        if ($package->type() === 'gitlab-oauth') {
            $io->setAuthentication('gitlab.com', $package->oauthToken(), 'oauth2');
        }

        if ($package->type() === 'bitbucket-oauth') {
            $io->setAuthentication('bitbucket.org', 'x-token-auth', $package->oauthToken());
        }

        return $io;
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

        return $config;
    }
}
