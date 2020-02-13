<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class RemoveOrganization
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function id(): string
    {
        return $this->id;
    }
}
