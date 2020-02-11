<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\PackageSynchronizer;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Service\PackageSynchronizer;
use Composer\Config;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Package\CompletePackage;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ComposerPackageSynchronizer implements PackageSynchronizer
{
    private string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function synchronize(Package $package): void
    {
        $io = new BufferIO('', OutputInterface::VERBOSITY_NORMAL);

        /** @var RepositoryInterface $repository */
        $repository = current(RepositoryFactory::defaultRepos($io, $this->createConfig($package)));
        $json = ['packages' => []];
        $packages = $repository->getPackages();
        $dumper = new ArrayDumper();

        if ($packages !== []) {
            $latest = current($packages);

            foreach ($packages as $p) {
                $json['packages'][$p->getPrettyName()][$p->getPrettyVersion()] = $dumper->dump($p);
                if ($p->getReleaseDate() > $latest->getReleaseDate()) {
                    $latest = $packages;
                }
            }

            $package->synchronize(
                $latest->getPrettyName(),
                $latest instanceof CompletePackage ? $latest->getDescription() : 'n/a',
                $latest->getPrettyVersion(),
                \DateTimeImmutable::createFromMutable($latest->getReleaseDate() ?? new \DateTime()),
            );

            $this->saveProvider($json, $latest);
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

    /**
     * @param mixed[] $json
     */
    private function saveProvider(array $json, PackageInterface $latest): void
    {
        $host = parse_url((string) $latest->getSourceUrl(), PHP_URL_HOST) ?? 'local';
        $filepath = $this->baseDir.'/'.$host.'/'.$latest->getPrettyName().'.json';

        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($filepath, serialize($json));
    }
}
