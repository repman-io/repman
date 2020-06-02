<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Entity\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User(Uuid::uuid4(), 'test@buddy.works', '4f6a2491-244a-4aef-8ec9-8dc36f7a10ce', ['ROLE_USER']);
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

    public function testConfirmEmailAddress(): void
    {
        $this->user->confirmEmail('4f6a2491-244a-4aef-8ec9-8dc36f7a10ce');

        self::assertNotNull($this->user->emailConfirmedAt());
    }

    public function testConfirmEmailAddressSetConfirmTimeOnlyOnce(): void
    {
        $this->user->confirmEmail('4f6a2491-244a-4aef-8ec9-8dc36f7a10ce');
        $time = $this->user->emailConfirmedAt();
        $this->user->confirmEmail('4f6a2491-244a-4aef-8ec9-8dc36f7a10ce');

        self::assertEquals($time, $this->user->emailConfirmedAt());
    }

    public function testConfirmEmailAddressWithInvalidToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->user->confirmEmail('wrong');
    }

    public function testEmailLowercase(): void
    {
        $user = new User(Uuid::uuid4(), 'tEsT@buDDy.woRKs', '4f6a2491-244a-4aef-8ec9-8dc36f7a10ce', ['ROLE_USER']);

        self::assertEquals($user->getEmail(), 'test@buddy.works');
    }

    public function testIgnoreIfIsAlreadyMember(): void
    {
        $organization = new Organization(Uuid::uuid4(), $this->user, 'repman', 'repman');
        $this->user->addMembership(new Member(Uuid::uuid4(), $this->user, $organization, Member::ROLE_MEMBER));

        self::assertEquals(1, $this->user->getOrganizations()->count());
    }
}
