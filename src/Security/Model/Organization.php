<?php

declare(strict_types=1);

namespace Buddy\Repman\Security\Model;

use Symfony\Component\Security\Core\User\UserInterface;

final class Organization implements UserInterface
{
    private string $id;
    private string $name;
    private string $token;

    public function __construct(string $id, string $name, string $token)
    {
        $this->id = $id;
        $this->name = $name;
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

    public function token(): string
    {
        return $this->token;
    }

    public function getRoles()
    {
        return ['ROLE_ORGANIZATION'];
    }

    public function getPassword()
    {
        return null;
    }

    public function getSalt()
    {
        return '';
    }

    public function getUsername()
    {
        return $this->token;
    }

    public function eraseCredentials(): void
    {
    }
}
