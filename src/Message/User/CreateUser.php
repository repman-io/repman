<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class CreateUser
{
    /**
     * @param array<string> $roles
     */
    public function __construct(private readonly string $id, private readonly string $email, private readonly string $plainPassword, private readonly string $confirmToken, private readonly array $roles = [])
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

    public function plainPassword(): string
    {
        return $this->plainPassword;
    }

    public function confirmToken(): string
    {
        return $this->confirmToken;
    }

    /**
     * @return array<string>
     */
    public function roles(): array
    {
        return $this->roles;
    }
}
