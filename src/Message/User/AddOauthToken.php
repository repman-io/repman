<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class AddOauthToken
{
    private string $id;
    private string $userId;
    private string $type;
    private string $value;

    public function __construct(string $id, string $userId, string $type, string $value)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->value = $value;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function value(): string
    {
        return $this->value;
    }
}
