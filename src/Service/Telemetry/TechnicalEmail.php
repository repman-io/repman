<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry;

use JsonSerializable;

final class TechnicalEmail implements JsonSerializable
{
    public function __construct(private readonly string $email, private readonly string $instanceId)
    {
    }

    public function instanceId(): string
    {
        return $this->instanceId;
    }

    /**
     * @return array<string,string>
     */
    public function jsonSerialize(): array
    {
        return [
            'email' => $this->email,
            'instanceId' => $this->instanceId,
        ];
    }
}
