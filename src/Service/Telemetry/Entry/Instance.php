<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

final class Instance
{
    private string $id;
    private string $version;
    private string $osVersion;
    private string $phpVersion;
    private int $users;
    private int $failedMessages;

    /**
     * @var array<string,string>
     */
    private array $config;

    /**
     * @param array<string,string> $config
     */
    public function __construct(
        string $id,
        string $version,
        string $osVersion,
        string $phpVersion,
        int $users,
        int $failedMessages,
        array $config
    ) {
        $this->id = $id;
        $this->version = $version;
        $this->osVersion = $osVersion;
        $this->phpVersion = $phpVersion;
        $this->users = $users;
        $this->failedMessages = $failedMessages;
        $this->config = $config;
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return array<string,array<string,string>|int|string>
     */
    public function toArray(): array
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
