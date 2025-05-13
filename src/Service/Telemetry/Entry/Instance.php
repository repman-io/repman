<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

use JsonSerializable;

final class Instance implements JsonSerializable
{
    /**
     * @param array<string,string> $config
     */
    public function __construct(private readonly string $id, private readonly string $version, private readonly string $osVersion, private readonly string $phpVersion, private readonly int $users, private readonly int $failedMessages, private readonly array $config)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'osVersion' => $this->osVersion,
            'phpVersion' => $this->phpVersion,
            'users' => $this->users,
            'config' => $this->config,
            'failedMessages' => $this->failedMessages,
        ];
    }
}
