<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy\MetadataProvider;

use Buddy\Repman\Service\Cache;
use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Json;
use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\MetadataProvider;
use Munus\Control\Option;

final class CacheableMetadataProvider implements MetadataProvider
{
    private Downloader $downloader;
    private Cache $cache;

    public function __construct(Downloader $downloader, Cache $cache)
    {
        $this->downloader = $downloader;
        $this->cache = $cache;
    }

    /**
     * @return Option<array<mixed>>
     */
    public function fromUrl(string $url, int $expireTime = 0): Option
    {
        $path = $this->getPath($url);

        return $this->cache->get($path, function () use ($url, $path, $expireTime): array {
            $content = $this->downloader->getContents($url)->getOrElseThrow(
                new \RuntimeException(sprintf('Failed to download metadata from %s', $url))
            );

            if ($expireTime === 0) {
                $this->cache->removeOld($path);
            }

            return Json::decode($content);
        }, $expireTime);
    }

    /**
     * @return Option<array<mixed>>
     */
    public function fromPath(string $package, string $repoUrl, int $expireTime = 0): Option
    {
        if (!$this->cache->exists($this->getPath($repoUrl.'/'.Proxy::PACKAGES_PATH), $expireTime)) {
            return Option::none();
        }

        return $this->cache->find((string) parse_url($repoUrl, PHP_URL_HOST).'/p/'.$package);
    }

    private function getPath(string $url): string
    {
        return (string) parse_url($url, PHP_URL_HOST).'/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');
    }
}
