<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Proxy\MetadataProvider;
use Buddy\Repman\Service\Proxy\PackageManager;
use Composer\Semver\VersionParser;
use Munus\Collection\GenericList;
use Munus\Control\Option;

final class Proxy
{
    public const PACKAGES_PATH = 'packages.json';
    public const PACKAGES_EXPIRE_TIME = 60;

    private string $url;
    private string $name;
    private MetadataProvider $metadataProvider;
    private Storage $distStorage;
    private PackageManager $packageManager;
    private VersionParser $versionParser;

    public function __construct(string $name, string $url, MetadataProvider $metadataProvider, Storage $distStorage, PackageManager $packageManager)
    {
        $this->name = $name;
        $this->url = rtrim($url, '/');
        $this->metadataProvider = $metadataProvider;
        $this->distStorage = $distStorage;
        $this->packageManager = $packageManager;
        $this->versionParser = new VersionParser();
    }

    /**
     * @return Option<resource>
     */
    public function distStream(string $package, string $version, string $ref, string $format): Option
    {
        $dist = new Dist($this->name, $package, $version, $ref, $format);
        if (!$this->distStorage->has($dist)) {
            $this->tryToDownload($package, $version, $dist);
        }

        return $this->distStorage->getStream($dist);
    }

    /**
     * @return Option<array<mixed>>
     */
    public function providerData(string $package, int $expireTime = self::PACKAGES_EXPIRE_TIME): Option
    {
        if (!($fromPath = $this->metadataProvider->fromPath('p/'.$package, $this->url, $expireTime))->isEmpty()) {
            return $fromPath;
        }

        $providerPath = $this->getProviderPath($package);
        if ($providerPath->isEmpty()) {
            return Option::none();
        }

        return $this->metadataProvider->fromUrl($this->getUrl($providerPath->get()));
    }

    /**
     * @return Option<array<mixed>>
     */
    public function providerDataV2(string $package, int $expireTime = self::PACKAGES_EXPIRE_TIME): Option
    {
        if (!($fromPath = $this->metadataProvider->fromPath('p2/'.$package, $this->url, $expireTime))->isEmpty()) {
            return $fromPath;
        }

        $providerPath = $this->getProviderPathV2($package);
        if ($providerPath->isEmpty()) {
            return Option::none();
        }

        return $this->metadataProvider->fromUrl($this->getUrl($providerPath->get()));
    }

    /**
     * @return GenericList<string>
     */
    public function syncedPackages(): GenericList
    {
        return $this->packageManager->packages($this->name);
    }

    public function downloadByVersion(string $package, string $version, bool $fromCache = true): void
    {
        $normalizedVersion = $this->versionParser->normalize($version);
        $providerData = $this->providerData($package, $fromCache ? 0 : -60)->getOrElse([]);

        foreach ($providerData['packages'][$package] ?? [] as $packageData) {
            $packageVersion = $packageData['version_normalized'] ?? $this->versionParser->normalize($packageData['version']);
            $packageDist = $packageData['dist'];

            if ($packageVersion !== $normalizedVersion && isset($packageDist['url'], $packageDist['reference'])) {
                $this->distStorage->download($packageDist['url'], new Dist(
                    $this->name,
                    $package,
                    $normalizedVersion,
                    $packageDist['reference'],
                    $packageDist['type']
                ));
                break;
            }
        }
    }

    /**
     * @return Option<string>
     */
    private function getProviderPath(string $packageName): Option
    {
        $root = $this->getRootPackages();
        if (isset($root['provider-includes'])) {
            foreach ($root['provider-includes'] as $url => $meta) {
                $data = $this->metadataProvider->fromUrl($this->getUrl(str_replace('%hash%', $meta['sha256'], $url)))->getOrElse([]);
                if (isset($data['providers'][$packageName])) {
                    return Option::some(
                        (string) str_replace(
                            ['%package%', '%hash%'],
                            [$packageName, $data['providers'][$packageName]['sha256']],
                            $root['providers-url']
                        )
                    );
                }
            }
        }

        return Option::none();
    }

    /**
     * @return Option<string>
     */
    private function getProviderPathV2(string $packageName): Option
    {
        $root = $this->getRootPackages();
        if (isset($root['metadata-url'])) {
            return Option::some(
                (string) str_replace(
                    ['%package%'],
                    [$packageName],
                    $root['metadata-url']
                )
            );
        }

        return Option::none();
    }

    /**
     * @return array<mixed>
     */
    private function getRootPackages(): array
    {
        return $this->metadataProvider->fromUrl($this->getUrl(self::PACKAGES_PATH), self::PACKAGES_EXPIRE_TIME)->getOrElse([]);
    }

    private function getUrl(string $path): string
    {
        return sprintf('%s/%s', $this->url, $path);
    }

    private function tryToDownload(string $package, string $version, Dist $dist, bool $fromCache = true): void
    {
        $providerData = $this->providerData($package, $fromCache ? 0 : -60)->getOrElse([]);
        foreach ($providerData['packages'][$package] ?? [] as $packageData) {
            $packageVersion = $packageData['version_normalized'] ?? $this->versionParser->normalize($packageData['version']);
            if (($packageVersion === $version || md5($packageVersion) === $version) && isset($packageData['dist']['url'])) {
                $this->distStorage->download($packageData['dist']['url'], $dist);

                return;
            }
        }

        if ($fromCache) {
            $this->tryToDownload($package, $version, $dist, false);
        }
    }
}
