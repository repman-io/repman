<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use Munus\Control\Option;

interface MetadataProvider
{
    /**
     * @return Option<array<mixed>>
     */
    public function fromUrl(string $url, int $expireTime = 0): Option;

    /**
     * @return Option<array<mixed>>
     */
    public function fromPath(string $package, string $repoUrl, int $expireTime = 0): Option;
}
