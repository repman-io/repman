<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Model;

final class User
{
    /**
     * @param string[] $roles
     */
    public function __construct(private readonly string $id, private readonly string $email, private readonly string $status, private readonly array $roles)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function status(): string
    {
        return $this->status;
    }

    /**
     * @return string[]
     */
    public function roles(): array
    {
        return $this->roles;
    }
}
