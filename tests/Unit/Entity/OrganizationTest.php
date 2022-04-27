<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Entity\Organization\Token;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Tests\MotherObject\PackageMother;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class OrganizationTest extends TestCase
{
    private Organization $org;
    private User $owner;

    protected function setUp(): void
    {
        $this->org = new Organization(Uuid::uuid4(), $this->owner = new User(Uuid::uuid4(), 'admin@buddy.works', Uuid::uuid4()->toString(), []), 'Buddy', 'buddy');
    }

    public function testOrganizationAddSameToken(): void
    {
        $token = new Token('secret', 'prod');

        $this->org->addToken($token);
        $this->org->addToken($token); // this should not throw exception

        $this->expectException(\RuntimeException::class);
        $token->setOrganization($this->org);
    }

    public function testOrganizationAddSamePackage(): void
    {
        $package = PackageMother::some();

        $this->org->addPackage($package);
        $this->org->addPackage($package); // this should not throw exception

        $this->expectException(\RuntimeException::class);
        $package->setOrganization($this->org);
    }

    public function testPreventDoubleInvitation(): void
    {
        $this->org->inviteUser('some@buddy.works', Member::ROLE_MEMBER, 'token');
        $this->org->inviteUser('some@buddy.works', Member::ROLE_MEMBER, 'token');

        $this->expectException(\InvalidArgumentException::class);
        $this->org->inviteUser('other@buddy.works', 'invalid-role', 'token');
    }

    public function testAcceptMissingInvitation(): void
    {
        $this->org->acceptInvitation('not-exist', new User(Uuid::uuid4(), 'user@buddy.works', Uuid::uuid4()->toString(), []));

        $this->expectException(\InvalidArgumentException::class);
        $this->org->inviteUser('user@buddy.works', 'invalid-role', 'token');
    }

    public function testInviteMember(): void
    {
        $this->org->inviteUser('some@buddy.works', Member::ROLE_MEMBER, 'token');
        $this->org->acceptInvitation('token', new User(Uuid::uuid4(), 'some@buddy.works', Uuid::uuid4()->toString(), []));
        $this->org->inviteUser('some@buddy.works', Member::ROLE_MEMBER, 'token');

        $this->expectException(\InvalidArgumentException::class);
        $this->org->inviteUser('other@buddy.works', 'invalid-role', 'token');
    }

    public function testIgnoreWhenUserTriesToAcceptNotOwnInvitation(): void
    {
        $this->org->inviteUser('some@buddy.works', Member::ROLE_MEMBER, 'token');
        $this->org->acceptInvitation('token', new User(Uuid::uuid4(), 'bad@buddy.works', Uuid::uuid4()->toString(), []));
        $this->org->removeInvitation('token');

        self::assertTrue($this->org->inviteUser('some@buddy.works', Member::ROLE_MEMBER, 'token'));
    }

    public function testPreventToOrphanOrganizationByRemovingLastOwner(): void
    {
        $this->org->inviteUser('some@buddy.works', Member::ROLE_OWNER, 'token');
        $this->org->acceptInvitation('token', $member = new User(Uuid::uuid4(), 'some@buddy.works', Uuid::uuid4()->toString(), []));
        $this->org->removeMember($this->owner);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Organisation must have at least one owner.');

        $this->org->removeMember($member);
    }

    public function testPreventToOrphanOrganizationByChangeRoleOfLastOwner(): void
    {
        $this->org->inviteUser('some@buddy.works', Member::ROLE_OWNER, 'token');
        $this->org->acceptInvitation('token', $member = new User(Uuid::uuid4(), 'some@buddy.works', Uuid::uuid4()->toString(), []));
        $this->org->changeRole($this->owner, Member::ROLE_MEMBER);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Organisation must have at least one owner.');

        $this->org->changeRole($member, Member::ROLE_MEMBER);
    }
}
