<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

use JsonSerializable;

final class Proxy implements JsonSerializable
{
    public function __construct(private readonly int $packages)
    {
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
