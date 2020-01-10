<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use Buddy\Repman\Service\Cache;
use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Proxy;

final class ProxyFactory
{
    private Downloader $downloader;
    private Cache $cache;
    private string $distsDir;

    public function __construct(Downloader $downloader, Cache $cache, string $distsDir)
    {
        $this->downloader = $downloader;
        $this->cache = $cache;
        $this->distsDir = $distsDir;
    }

    public function create(string $url): Proxy
    {
        return new Proxy(
            (string) parse_url($url, PHP_URL_HOST),
            $url,
            $this->downloader,
            $this->cache,
            $this->distsDir
        );
    }
}
