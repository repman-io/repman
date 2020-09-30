<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class AddPackage
{
    private string $id;
    private string $url;
    private string $type;
    private string $organizationId;
    private int $versionsLimit;

    /**
     * @var mixed[]
     */
    private array $metadata;

    /**
     * @param mixed[] $metadata
     */
    public function __construct(string $id, string $organizationId, string $url, string $type = 'vcs', array $metadata = [], ?int $versionsLimit = null)
    {
        $this->id = $id;
        $this->organizationId = $organizationId;
        $this->url = $url;
        $this->type = $type;
        $this->metadata = $metadata;
        $this->versionsLimit = $versionsLimit ?? 0;
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

    public function versionsLimit(): int
    {
        return $this->versionsLimit;
    }
}
