<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

use JsonSerializable;

final class Downloads implements JsonSerializable
{
    public function __construct(private readonly int $proxy, private readonly int $private)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'proxy' => $this->proxy,
            'private' => $this->private,
        ];
    }
}
