<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class RemovePackage
{
    private string $id;
    private string $organizationId;

    public function __construct(string $id, string $organizationId)
    {
        $this->id = $id;
        $this->organizationId = $organizationId;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }
}
