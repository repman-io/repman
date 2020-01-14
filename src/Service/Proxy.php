<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Proxy\MetadataProvider;
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

    public function __construct(string $name, string $url, MetadataProvider $metadataProvider, Storage $distStorage)
    {
        $this->name = $name;
        $this->url = rtrim($url, '/');
        $this->metadataProvider = $metadataProvider;
        $this->distStorage = $distStorage;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Option<string>
     */
    public function distFilename(string $package, string $version, string $ref, string $format): Option
    {
        $dist = new Dist($this->name, $package, $version, $ref, $format);
        if (!$this->distStorage->has($dist)) {
            $providerData = $this->providerData($package)->getOrElse([]);
            if (!isset($providerData['packages'][$package])) {
                return Option::none();
            }
            $parser = new VersionParser();
            foreach ($providerData['packages'][$package] as $packageVersion) {
                if (!isset($packageVersion['version_normalized'])) {
                    $packageVersion['version_normalized'] = $parser->normalize($packageVersion['version']);
                }

                if ($packageVersion['version_normalized'] === $version && isset($packageVersion['dist']['url'])) {
                    $this->distStorage->download($packageVersion['dist']['url'], $dist);
                }
            }
        }
        $distFilename = $this->distStorage->filename($dist);

        return Option::when(file_exists($distFilename), $distFilename);
    }

    /**
     * @return Option<array<mixed>>
     */
    public function providerData(string $package): Option
    {
        $providerPath = $this->getProviderPath($package);
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
        return $this->distStorage->packages($this->name);
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
}
