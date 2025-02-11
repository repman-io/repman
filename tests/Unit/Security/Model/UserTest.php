<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Security\Model;

use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Security\Model\Organization;
use Buddy\Repman\Security\Model\User;
use Buddy\Repman\Tests\MotherObject\Security\UserMother;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = UserMother::withoutOrganizations();
    }

    public function testNoneWhenNoOrganizations(): void
    {
        $this->assertTrue(Option::none()->equals($this->user->firstOrganizationAlias()));
    }

    public function testIsEqualTo(): void
    {
        $this->assertTrue($this->user->isEqualTo(UserMother::withoutOrganizations()));

        $this->assertFalse($this->user->isEqualTo(UserMother::withoutOrganizations('other@repman.io')));
        $this->assertFalse($this->user->isEqualTo(UserMother::withRoles(['ROLE_ADMIN'])));
        $this->assertFalse($this->user->isEqualTo(new Organization('id', 'name', 'alias', 'token')));
    }

    public function testIsMemberOfOrganization(): void
    {
        $organization = new User\Organization('buddy', 'Buddy', Member::ROLE_MEMBER, true);
        $this->assertFalse(UserMother::withOrganizations(Uuid::uuid4()->toString(), [$organization])->isMemberOfOrganization('test'));
        $this->assertFalse(UserMother::withOrganizations(Uuid::uuid4()->toString(), [$organization])->isMemberOfOrganization('Buddy'));
        $this->assertTrue(UserMother::withOrganizations(Uuid::uuid4()->toString(), [$organization])->isMemberOfOrganization('buddy'));
    }

    public function testGetUsername(): void
    {
        $this->assertSame('test@repman.io', $this->user->getUsername());
    }
}
