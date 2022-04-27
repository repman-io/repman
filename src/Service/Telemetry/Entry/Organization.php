<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

final class Organization implements \JsonSerializable
{
    private string $id;
    private int $tokens;
    private bool $public;
    private int $members;
    private int $owners;

    /**
     * @var Package[]
     */
    private array $packages = [];

    public function __construct(string $id, int $tokens, bool $public, int $members, int $owners)
    {
        $this->id = $id;
        $this->tokens = $tokens;
        $this->public = $public;
        $this->members = $members;
        $this->owners = $owners;
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
