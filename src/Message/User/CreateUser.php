<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class CreateUser
{
    private string $id;
    private string $email;
    private string $plainPassword;

    /**
     * @var array<string>
     */
    private array $roles;

    /**
     * @param array<string> $roles
     */
    public function __construct(string $id, string $email, string $plainPassword, array $roles = [])
    {
        $this->id = $id;
        $this->email = $email;
        $this->plainPassword = $plainPassword;
        $this->roles = $roles;
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

    /**
     * @return array<string>
     */
    public function roles(): array
    {
        return $this->roles;
    }
}
