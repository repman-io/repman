<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization\Member;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Security\Model\User\Organization;
use Buddy\Repman\Service\Organization\OrganizationVoter;
use Buddy\Repman\Tests\MotherObject\Query\OrganizationMother;
use Buddy\Repman\Tests\MotherObject\Security\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class OrganizationVoterTest extends TestCase
{
    private OrganizationVoter $voter;
    private UsernamePasswordToken $token;
    private string $userId;

    protected function setUp(): void
    {
        $this->userId = 'some-id';
        $this->token = new UsernamePasswordToken(UserMother::withOrganizations($this->userId, [
            new Organization('repman', 'name', 'owner', false),
            new Organization('buddy', 'name', 'member', false),
        ]), 'password', ['key']);
        $this->voter = new OrganizationVoter(self::createStub(OrganizationQuery::class));
    }

    public function testAbstainForAnonymousUser(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote(
            new NullToken(),
            'any',
            ['ROLE_ORGANIZATION_MEMBER']
        ));
    }

    public function testDenyIfNoSubject(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(
            $this->token,
            null,
            ['ROLE_ORGANIZATION_MEMBER']
        ));
    }

    public function testAccessForOwnerWithOrganizationReadModel(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $this->token,
            OrganizationMother::withMember(new Member($this->userId, 'email', 'owner')),
            ['ROLE_ORGANIZATION_OWNER']
        ));

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(
            $this->token,
            OrganizationMother::withMember(new Member($this->userId, 'email', 'member')),
            ['ROLE_ORGANIZATION_OWNER']
        ));

        // other organization
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(
            $this->token,
            OrganizationMother::withMember(new Member('other-id', 'email', 'member')),
            ['ROLE_ORGANIZATION_OWNER']
        ));

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(
            $this->token,
            OrganizationMother::withMember(new Member('other-id', 'email', 'member')),
            ['ROLE_ORGANIZATION_MEMBER']
        ));
    }

    public function testAccessForOwnerWithRequest(): void
    {
        // owner organization
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $this->token,
            new Request([], [], ['organization' => 'repman']),
            ['ROLE_ORGANIZATION_OWNER']
        ));

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $this->token,
            new Request([], [], ['organization' => 'repman']),
            ['ROLE_ORGANIZATION_MEMBER']
        ));

        // member organization
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(
            $this->token,
            new Request([], [], ['organization' => 'buddy']),
            ['ROLE_ORGANIZATION_OWNER']
        ));

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $this->token,
            new Request([], [], ['organization' => 'buddy']),
            ['ROLE_ORGANIZATION_MEMBER']
        ));

        // other organization
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(
            $this->token,
            new Request([], [], ['organization' => 'other']),
            ['ROLE_ORGANIZATION_MEMBER']
        ));

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(
            $this->token,
            new Request([], [], ['organization' => 'other']),
            ['ROLE_ORGANIZATION_OWNER']
        ));

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote(
            new NullToken(),
            new Request([], [], ['organization' => 'buddy']),
            []
        ));
    }
}
