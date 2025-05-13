<?php

declare(strict_types=1);

namespace Buddy\Repman\Security\Model;

use Symfony\Component\Security\Core\User\UserInterface;

final class Organization implements UserInterface
{
    public function __construct(private readonly string $id, private readonly string $name, private readonly string $alias, private readonly string $token)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function getRoles(): array
    {
        return ['ROLE_ORGANIZATION'];
    }

    public function getPassword(): string
    {
        return $this->token;
    }

    public function getSalt(): string
    {
        return '';
    }

    public function getUsername(): string
    {
        return $this->alias;
    }

    public function getUserIdentifier(): string
    {
        return $this->alias;
    }

    public function eraseCredentials(): void
    {
    }
}
