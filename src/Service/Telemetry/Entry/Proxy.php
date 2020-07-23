<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

final class Proxy implements \JsonSerializable
{
    private int $packages;

    public function __construct(int $packages)
    {
        $this->packages = $packages;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'packages' => $this->packages,
        ];
    }
}
