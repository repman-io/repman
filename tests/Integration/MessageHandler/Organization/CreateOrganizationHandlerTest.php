<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreateOrganizationHandlerTest extends IntegrationTestCase
{
    public function testCreateOrganization(): void
    {
        /** @var User */
        $owner = $this->entityManager()
            ->getRepository(User::class)
            ->findOneBy([]);
        $name = ' - Test organization  ŹĆŻŁ !@#$%^&*() ';

        $this->container()
            ->get(MessageBusInterface::class)
            ->dispatch(new CreateOrganization(
                $id = Uuid::uuid4()->toString(),
                $owner->getId()->toString(),
                $name
            ));

        $organization = $this->entityManager()
            ->getRepository(Organization::class)
            ->find($id);

        self::assertInstanceOf(Organization::class, $organization);
        self::assertEquals($id, $organization->getId()->toString());

        // check associations
        self::assertEquals($owner->getOrganizations()[0]->getId(), $organization->getId());
        self::assertEquals($owner->getId(), $organization->getOwner()->getId());

        // check fields
        self::assertEquals($name, $organization->getName());
        self::assertEquals('test-organization-z-czl', $organization->getAlias());
    }
}
