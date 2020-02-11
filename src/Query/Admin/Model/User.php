<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Model;

final class User
{
    private string $id;
    private string $email;
    private string $status;

    /**
     * @var string[]
     */
    private array $roles;

    /**
     * @param string[] $roles
     */
    public function __construct(string $id, string $email, string $status, array $roles)
    {
        $this->id = $id;
        $this->email = $email;
        $this->status = $status;
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
