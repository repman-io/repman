<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\MotherObject\Security;

use Buddy\Repman\Security\Model\User;
use Buddy\Repman\Security\Model\User\Organization;
use Ramsey\Uuid\Uuid;

final class UserMother
{
    public static function withoutOrganizations(string $email = 'test@repman.io'): User
    {
        return new User(Uuid::uuid4()->toString(), $email, 'password', 'enabled', true, 'token', [], [], true, 'UTC');
    }

    /**
     * @param Organization[] $organizations
     */
    public static function withOrganizations(string $id, array $organizations): User
    {
        return new User($id, 'test@repman.io', 'password', 'enabled', true, 'token', [], $organizations, true, 'UTC');
    }

    /**
     * @param string[] $roles
     */
    public static function withRoles(array $roles = []): User
    {
        return new User(Uuid::uuid4()->toString(), 'test@repman.io', 'password', 'enabled', true, 'token', $roles, [], true, 'UTC');
    }
}
