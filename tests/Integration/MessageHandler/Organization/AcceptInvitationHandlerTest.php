<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization\Member as DomainMember;
use Buddy\Repman\Message\Organization\Member\AcceptInvitation;
use Buddy\Repman\Query\User\Model\Organization\Member;
use Buddy\Repman\Query\User\OrganizationQuery\DbalOrganizationQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class AcceptInvitationHandlerTest extends IntegrationTestCase
{
    public function testAcceptInvitation(): void
    {
        $organizationId = $this->fixtures->createOrganization('repman', $this->fixtures->createUser());
        $this->fixtures->inviteUser($organizationId, $email = 'some@repman.io', $token = 'bedfa60b-5a4b-478d-ac86-73d1d9c5a6fd');
        $invitedUser = $this->fixtures->createUser($email);

        $this->dispatchMessage(new AcceptInvitation($token, $invitedUser));

        /** @var DbalOrganizationQuery $query */
        $query = $this->container()->get(DbalOrganizationQuery::class);
        self::assertEquals(0, $query->invitationsCount($organizationId));
        self::assertEquals([], $query->findAllInvitations($organizationId));

        self::assertEquals(1, $query->membersCount($organizationId));
        self::assertEquals([new Member($invitedUser, $email, DomainMember::ROLE_MEMBER)], $query->findAllMembers($organizationId));
    }
}
