<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Member;

final class AcceptInvitation
{
    public function __construct(private readonly string $token, private readonly string $userId)
    {
    }

    public function token(): string
    {
        return $this->token;
    }

    public function userId(): string
    {
        return $this->userId;
    }
}
