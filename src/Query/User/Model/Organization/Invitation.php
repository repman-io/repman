<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Organization;

final class Invitation
{
    private string $email;
    private string $role;

    public function __construct(string $email, string $role)
    {
        $this->email = $email;
        $this->role = $role;
    }
}
