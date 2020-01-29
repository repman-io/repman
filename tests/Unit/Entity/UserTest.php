<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity;

use Buddy\Repman\Entity\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User(Uuid::uuid4(), 'test@buddy.works', ['ROLE_USER']);
    }

    public function testResetPassword(): void
    {
        $this->user->setResetPasswordToken('token');
        $this->user->resetPassword('token', 'secret', 3600);

        self::assertEquals('secret', $this->user->getPassword());
    }

    public function testResetPasswordWithInvalidToken(): void
    {
        $this->user->setResetPasswordToken('token');

        $this->expectException(\InvalidArgumentException::class);

        $this->user->resetPassword('other', 'secret', 3600);
    }

    public function testResetPasswordWhenTokenExpired(): void
    {
        $this->user->setResetPasswordToken('token');

        $this->expectException(\InvalidArgumentException::class);

        $this->user->resetPassword('token', 'secret', -1);
    }
}
