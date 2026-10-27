<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Proxy;
use League\Flysystem\FilesystemOperator;

final class ProxyFactory
{
    private Downloader $downloader;
    private FilesystemOperator $filesystem;

    public function __construct(Downloader $downloader, FilesystemOperator $proxyFilesystem)
    {
        $this->downloader = $downloader;
        $this->filesystem = $proxyFilesystem;
    }

    public function create(string $url): Proxy
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        return new Proxy(
            $host,
            $url,
            $this->filesystem,
            $this->downloader,
            // packagist.org itself refuses Repman; repo.packagist.org is its metadata mirror for tools
            $host === 'packagist.org' ? 'https://repo.packagist.org' : null,
        );
    }
}
