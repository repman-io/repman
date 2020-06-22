<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Proxy;

final class ProxyFactory
{
    private MetadataProvider $metadataProvider;
    private Storage $distStorage;
    private PackageManager $packageManager;

    public function __construct(MetadataProvider $metadataProvider, Storage $distStorage, PackageManager $packageManager)
    {
        $this->metadataProvider = $metadataProvider;
        $this->distStorage = $distStorage;
        $this->packageManager = $packageManager;
    }

    public function create(string $url): Proxy
    {
        return new Proxy(
            (string) parse_url($url, PHP_URL_HOST),
            $url,
            $this->metadataProvider,
            $this->distStorage,
            $this->packageManager
        );
    }
}
