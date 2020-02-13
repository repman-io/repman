<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\PackageSynchronizer;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\PackageNormalizer;
use Buddy\Repman\Service\PackageSynchronizer;
use Composer\Config;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Package\CompletePackage;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ComposerPackageSynchronizer implements PackageSynchronizer
{
    private PackageManager $packageManager;
    private PackageNormalizer $packageNormalizer;

    public function __construct(PackageManager $packageManager, PackageNormalizer $packageNormalizer)
    {
        $this->packageManager = $packageManager;
        $this->packageNormalizer = $packageNormalizer;
    }

    public function synchronize(Package $package): void
    {
        $io = new BufferIO('', OutputInterface::VERBOSITY_VERY_VERBOSE);

        try {
            /** @var RepositoryInterface $repository */
            $repository = current(RepositoryFactory::defaultRepos($io, $this->createConfig($package)));
            $json = ['packages' => []];
            $packages = $repository->getPackages();

            $latest = current($packages);

            foreach ($packages as $p) {
                $json['packages'][$p->getPrettyName()][$p->getPrettyVersion()] = $this->packageNormalizer->normalize($p);
                if ($p->getReleaseDate() > $latest->getReleaseDate()) {
                    $latest = $p;
                }
            }

            $package->syncSuccess(
                $latest->getPrettyName(),
                $latest instanceof CompletePackage ? ($latest->getDescription() ?? 'n/a') : 'n/a',
                $latest->getPrettyVersion(),
                \DateTimeImmutable::createFromMutable($latest->getReleaseDate() ?? new \DateTime()),
            );

            $this->packageManager->saveProvider($json, $package->organizationAlias(), $latest->getPrettyName());
        } catch (\Throwable $exception) {
            $package->syncFailure(sprintf("Error: %s\nLogs:\n%s", $exception->getMessage(), $io->getOutput()));
        }
    }

    private function createConfig(Package $package): Config
    {
        unset(Config::$defaultRepositories['packagist.org']);
        $config = Factory::createConfig();
        $config->merge(['repositories' => [
            ['type' => $package->type(), 'url' => $package->repositoryUrl()],
        ]]);

        return $config;
    }
}
