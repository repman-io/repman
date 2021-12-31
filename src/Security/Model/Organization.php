<?php

declare(strict_types=1);

namespace Buddy\Repman\Security\Model;

use Symfony\Component\Security\Core\User\UserInterface;

final class Organization implements UserInterface
{
    private string $id;
    private string $name;
    private string $alias;
    private string $token;

    public function __construct(string $id, string $name, string $alias, string $token)
    {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
        $this->token = $token;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function getRoles()
    {
        return ['ROLE_ORGANIZATION'];
    }

    public function getPassword()
    {
        return $this->token;
    }

    public function getSalt()
    {
        return '';
    }

    public function getUsername()
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
