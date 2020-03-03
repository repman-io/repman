<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\PackageSynchronizer;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\User\OauthToken;
use Buddy\Repman\Repository\PackageRepository;
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

    public function __construct(PackageManager $packageManager, PackageNormalizer $packageNormalizer, PackageRepository $packageRepository)
    {
        $this->packageManager = $packageManager;
        $this->packageNormalizer = $packageNormalizer;
        $this->packageRepository = $packageRepository;
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
            if (!$package->isSynchronized() & $this->packageExist($name)) {
                throw new \RuntimeException("Package {$name} already exists. Package name must be unique within organization.");
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

    private function createConfig(Package $package): Config
    {
        unset(Config::$defaultRepositories['packagist.org']);
        $config = Factory::createConfig();
        $params = [
            'repositories' => [
                [
                    'type' => $package->type(),
                    'url' => $package->repositoryUrl(),
                ],
            ],
            'config' => [],
        ];

        $map = [
            OauthToken::TYPE_GITHUB => [
                'key' => 'github-oauth',
                'domain' => 'github.com',
            ],
            OauthToken::TYPE_GITLAB => [
                'key' => 'gitlab-oauth',
                'domain' => 'gitlab.com',
            ],
        ];

        if ($token = $package->oauthToken()) {
            $type = $token->type();
            $params['config'][$map[$type]['key']] = [$map[$type]['domain'] => $token->value()];
        }

        $config->merge($params);

        return $config;
    }

    private function packageExist(string $name): bool
    {
        return $this->packageRepository->findOneBy(['name' => $name]) instanceof Package;
    }
}
