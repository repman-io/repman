<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Repository;

use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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

        $this->users->upgradePassword(new class implements PasswordAuthenticatedUserInterface, UserInterface {
            public function getPassword(): string
            {
                return 'hash';
            }

            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return 'someone@else.io';
            }
        }, 'password');
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
