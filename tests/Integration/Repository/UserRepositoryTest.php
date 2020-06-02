<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Repository;

use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\Organization;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

final class UserRepositoryTest extends IntegrationTestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = $this->container()->get(UserRepository::class);
    }

    public function testUpgradePasswordOnlyForUserClass(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $this->users->upgradePassword(new Organization('id', 'name', 'alias', 'token'), 'password');
    }

    public function testThrowExceptionWhenNotFoundByEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->users->getByEmail('not@exist.com');
    }

    public function testThrowExceptionWhenNotFoundById(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->users->getById(Uuid::uuid4());
    }
}
