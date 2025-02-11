<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class AddPackage
{
    private readonly int $keepLastReleases;

    /**
     * @param mixed[] $metadata
     */
    public function __construct(private readonly string $id, private readonly string $organizationId, private readonly string $url, private readonly string $type = 'vcs', private readonly array $metadata = [], ?int $keepLastReleases = null)
    {
        $this->keepLastReleases = $keepLastReleases ?? 0;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return mixed[]
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function keepLastReleases(): int
    {
        return $this->keepLastReleases;
    }
}
