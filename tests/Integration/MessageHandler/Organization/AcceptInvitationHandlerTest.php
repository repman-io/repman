<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization\Member as DomainMember;
use Buddy\Repman\Message\Organization\Member\AcceptInvitation;
use Buddy\Repman\Query\Filter;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Member;
use Buddy\Repman\Query\User\OrganizationQuery\DbalOrganizationQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class AcceptInvitationHandlerTest extends IntegrationTestCase
{
    public function testAcceptInvitation(): void
    {
        $organizationId = $this->fixtures->createOrganization('repman', $ownerId = $this->fixtures->createUser($ownerEmail = 'owner@repman.io'));
        $this->fixtures->inviteUser($organizationId, $invitedEmail = 'some@repman.io', $token = 'bedfa60b-5a4b-478d-ac86-73d1d9c5a6fd');
        $invitedId = $this->fixtures->createUser($invitedEmail);

        $this->dispatchMessage(new AcceptInvitation($token, $invitedId));

        /** @var DbalOrganizationQuery $query */
        $query = $this->container()->get(DbalOrganizationQuery::class);
        $this->assertSame(0, $query->invitationsCount($organizationId));
        $this->assertSame([], $query->findAllInvitations($organizationId, new Filter()));

        /** @var Organization $organization */
        $organization = $query->getByAlias('repman')->get();
        $this->assertTrue($organization->isOwner($ownerId));
        $this->assertFalse($organization->isOwner($invitedId));

        $this->assertSame(2, $query->membersCount($organizationId));
        $this->assertEquals([
            new Member($ownerId, $ownerEmail, DomainMember::ROLE_OWNER),
            new Member($invitedId, $invitedEmail, DomainMember::ROLE_MEMBER),
        ], $query->findAllMembers($organizationId, new Filter()));
    }
}
