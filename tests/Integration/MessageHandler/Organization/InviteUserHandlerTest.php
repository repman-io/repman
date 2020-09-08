<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Message\Organization\Member\InviteUser;
use Buddy\Repman\Query\Filter;
use Buddy\Repman\Query\User\Model\Organization\Invitation;
use Buddy\Repman\Query\User\OrganizationQuery\DbalOrganizationQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class InviteUserHandlerTest extends IntegrationTestCase
{
    public function testInviteUser(): void
    {
        $organizationId = $this->fixtures->createOrganization('repman', $this->fixtures->createUser());

        $this->dispatchMessage(new InviteUser('some@repman.io', Member::ROLE_MEMBER, $organizationId, $token = 'feb0f4ce-32c7-45a2-b020-870879a515d9'));

        /** @var DbalOrganizationQuery $query */
        $query = $this->container()->get(DbalOrganizationQuery::class);
        self::assertEquals(1, $query->invitationsCount($organizationId));
        self::assertEquals([new Invitation('some@repman.io', Member::ROLE_MEMBER, $token)], $query->findAllInvitations($organizationId, new Filter()));
    }
}
