<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

use JsonSerializable;

final class Organization implements JsonSerializable
{
    /**
     * @var Package[]
     */
    private array $packages = [];

    public function __construct(private readonly string $id, private readonly int $tokens, private readonly bool $public, private readonly int $members, private readonly int $owners)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @param Package[] $packages
     */
    public function addPackages(array $packages): void
    {
        $this->packages = array_merge($this->packages, $packages);
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'packages' => $this->packages,
            'tokens' => $this->tokens,
            'public' => $this->public,
            'members' => $this->members,
            'owners' => $this->owners,
        ];
    }
}
