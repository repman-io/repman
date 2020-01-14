<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Proxy;

final class ProxyFactory
{
    private MetadataProvider $metadataProvider;
    private Storage $distStorage;

    public function __construct(MetadataProvider $metadataProvider, Storage $distStorage)
    {
        $this->metadataProvider = $metadataProvider;
        $this->distStorage = $distStorage;
    }

    public function create(string $url): Proxy
    {
        return new Proxy(
            (string) parse_url($url, PHP_URL_HOST),
            $url,
            $this->metadataProvider,
            $this->distStorage
        );
    }
}
