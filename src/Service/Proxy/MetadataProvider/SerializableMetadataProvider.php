<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy\MetadataProvider;

use Buddy\Repman\Service\Cache;
use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Json;
use Buddy\Repman\Service\Proxy\MetadataProvider;
use Munus\Control\Option;

final class SerializableMetadataProvider implements MetadataProvider
{
    private Downloader $downloader;
    private Cache $cache;

    public function __construct(Downloader $downloader, Cache $cache)
    {
        $this->downloader = $downloader;
        $this->cache = $cache;
    }

    public function fromUrl(string $url, int $expireTime = 0): Option
    {
        $path = (string) parse_url($url, PHP_URL_HOST).'/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        return $this->cache->get($path, function () use ($url, $path, $expireTime) {
            $content = $this->downloader->getContents($url)->getOrElseThrow(
                new \RuntimeException(sprintf('Failed to download metadata from %s', $url))
            );

            if ($expireTime === 0) {
                $this->cache->removeOld($path);
            }

            return Json::decode($content);
        }, $expireTime);
    }
}
