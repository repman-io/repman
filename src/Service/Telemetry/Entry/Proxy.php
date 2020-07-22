<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

final class Proxy
{
    private int $packages;

    public function __construct(int $packages)
    {
        $this->packages = $packages;
    }

    /**
     * @return array<string,int>
     */
    public function toArray(): array
    {
        return [
            'packages' => $this->packages,
        ];
    }
}
