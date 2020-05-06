<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Service\Organization\OrganizationVoter;
use Munus\Control\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrganizationVoterTest extends TestCase
{
    private MockObject $organizationQuery;
    private OrganizationVoter $voter;
    private UsernamePasswordToken $token;
    private UuidInterface $userId;

    protected function setUp(): void
    {
        $this->token = new UsernamePasswordToken(new User($this->userId = Uuid::uuid4(), 'some@repman.io', 'token', []), 'password', 'key');
        $this->organizationQuery = $this->createMock(OrganizationQuery::class);
        $this->voter = new OrganizationVoter($this->organizationQuery);
    }

    public function testSupportOnlyUserInstance(): void
    {
        self::assertEquals(Voter::ACCESS_DENIED, $this->voter->vote(
            new AnonymousToken('secret', 'anon', []),
            'any',
            ['ROLE_ORGANIZATION_MEMBER']
        ));
    }

    public function testDenyIfNoSubject(): void
    {
        self::assertEquals(Voter::ACCESS_DENIED, $this->voter->vote(
            $this->token,
            null,
            ['ROLE_ORGANIZATION_MEMBER']
        ));
    }

    public function testAccessForOwnerWithOrganizationReadModel(): void
    {
        self::assertEquals(Voter::ACCESS_GRANTED, $this->voter->vote(
            $this->token,
            new Organization('id', 'name', 'alias', [
                new Organization\Member($this->userId->toString(), 'email', 'owner'),
            ]),
            ['ROLE_ORGANIZATION_OWNER']
        ));
        self::assertEquals(Voter::ACCESS_DENIED, $this->voter->vote(
            $this->token,
            new Organization('id', 'name', 'alias', [
                new Organization\Member($this->userId->toString(), 'email', 'member'),
            ]),
            ['ROLE_ORGANIZATION_OWNER']
        ));
    }

    public function testAccessForOwnerWithRequest(): void
    {
        $this->organizationQuery->expects(self::once())->method('getByAlias')->willReturn(Option::of(
            new Organization('id', 'name', 'alias', [
                new Organization\Member($this->userId->toString(), 'email', 'owner'),
            ])
        ));

        self::assertEquals(Voter::ACCESS_GRANTED, $this->voter->vote(
            $this->token,
            new Request([], ['organization' => 'alias']),
            ['ROLE_ORGANIZATION_OWNER']
        ));
    }
}
