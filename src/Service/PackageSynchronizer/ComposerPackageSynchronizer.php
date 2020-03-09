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
use Composer\Package\CompletePackage;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryInterface;
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

            $name = $latest->getPrettyName();
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
                $latest->getPrettyVersion(),
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

    private function createConfig(Package $package): Config
    {
        unset(Config::$defaultRepositories['packagist.org']);
        $config = Factory::createConfig();

        $map = [
            'github-oauth' => [
                'domain' => 'github.com',
            ],
            'gitlab-oauth' => [
                'domain' => 'gitlab.com',
            ],
        ];

        $type = array_key_exists($package->type(), $map) ? 'vcs' : $package->type();

        $params = [
            'repositories' => [
                [
                    'type' => $type,
                    'url' => $package->repositoryUrl(),
                ],
            ],
            'config' => [],
        ];

        if (isset($map[$package->type()]) && $package->hasOAuthToken()) {
            $params['config'][$package->type()] = [$map[$package->type()]['domain'] => $package->oauthToken()];
        }

        $config->merge($params);

        return $config;
    }
}
