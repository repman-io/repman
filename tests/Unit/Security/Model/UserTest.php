<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Security\Model;

use Buddy\Repman\Security\Model\Organization;
use Buddy\Repman\Security\Model\User;
use Buddy\Repman\Tests\MotherObject\Security\UserMother;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = UserMother::withoutOrganizations();
    }

    public function testNoneWhenNoOrganizations(): void
    {
        self::assertTrue(Option::none()->equals($this->user->firstOrganizationAlias()));
    }

    public function testIsEqualTo(): void
    {
        self::assertTrue($this->user->isEqualTo(UserMother::withoutOrganizations()));

        self::assertFalse($this->user->isEqualTo(UserMother::withoutOrganizations('other@repman.io')));
        self::assertFalse($this->user->isEqualTo(UserMother::withRoles(['ROLE_ADMIN'])));
        self::assertFalse($this->user->isEqualTo(new Organization('id', 'name', 'alias', 'token')));
    }

    public function testGetUsername(): void
    {
        self::assertSame('test@repman.io', $this->user->getUsername());
    }
}
