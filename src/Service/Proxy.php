<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

final class Proxy
{
    private string $baseUrl;

    /**
     * @return array<mixed>
     */
    public function provider(string $packageName): array
    {
        return ['packages', []];
    }
}
