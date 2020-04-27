<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Member;

final class AcceptInvitation
{
    private string $token;
    private string $userId;

    public function __construct(string $token, string $userId)
    {
        $this->token = $token;
        $this->userId = $userId;
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
