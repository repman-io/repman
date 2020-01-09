<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Munus\Control\Option;

final class Proxy
{
    public const PACKAGES_PATH = 'packages.json';

    private string $url;
    private string $name;
    private RemoteFilesystem $remoteFilesystem;
    private Cache $cache;

    public function __construct(string $name, string $url, RemoteFilesystem $remoteFilesystem, Cache $cache)
    {
        $this->name = $name;
        $this->url = rtrim($url, '/');
        $this->remoteFilesystem = $remoteFilesystem;
        $this->cache = $cache;
    }

    /**
     * @return Option<array<mixed>>
     */
    public function provider(string $packageName): Option
    {
        $providerPath = $this->getProviderPath($packageName);
        if ($providerPath->isEmpty()) {
            return Option::none();
        }

        $contents = $this->cache->get($this->getCachePath($providerPath->get()), fn () => $this->remoteFilesystem->getContents($this->url.'/'.$providerPath->get())->getOrElse(''));
        if ($contents->isEmpty()) {
            return Option::none();
        }

        return Option::some(Json::decode($contents->get()));
    }

    /**
     * @return Option<string>
     */
    private function getProviderPath(string $packageName): Option
    {
        $root = $this->getRootPackages();
        if (isset($root['provider-includes'])) {
            foreach ($root['provider-includes'] as $url => $meta) {
                $filename = str_replace('%hash%', $meta['sha256'], $url);
                $contents = $this->cache->get($this->getCachePath($filename), fn () => $this->remoteFilesystem->getContents($this->url.'/'.$filename)->getOrElse(''));
                $data = Json::decode($contents->getOrElse('{}'));
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
        $contents = $this->cache->get($this->getCachePath(self::PACKAGES_PATH), function (): string {
            return $this->remoteFilesystem->getContents($this->getUrl(self::PACKAGES_PATH))->getOrElse('');
        });

        return Json::decode($contents->getOrElse('{}'));
    }

    private function getUrl(string $path): string
    {
        return sprintf('%s/%s', $this->url, $path);
    }

    private function getCachePath(string $path): string
    {
        return sprintf('%s/%s', $this->name, $path);
    }
}
