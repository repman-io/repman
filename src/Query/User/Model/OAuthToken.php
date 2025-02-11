<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use DateTimeImmutable;

final class OAuthToken
{
    public function __construct(private readonly string $type, private readonly DateTimeImmutable $createdAt)
    {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
