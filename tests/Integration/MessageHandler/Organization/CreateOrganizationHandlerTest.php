<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Munus\Control\Option;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class CreateOrganizationHandlerTest extends IntegrationTestCase
{
    public function testSuccess(): void
    {
        $owner = $this->sampleUser();
        $name = 'Acme Inc.';

        $error = $this->createOrganization(
            $id = Uuid::uuid4()->toString(),
            $owner->getId()->toString(),
            $name
        );

        $organization = $this->entityManager()
            ->getRepository(Organization::class)
            ->find($id);

        self::assertTrue($error->isEmpty());

        self::assertInstanceOf(Organization::class, $organization);
        self::assertEquals($id, $organization->getId()->toString());

        // check associations
        self::assertEquals($owner->getOrganizations()[0]->getId(), $organization->getId());
        self::assertEquals($owner->getId(), $organization->getOwner()->getId());

        // check fields
        self::assertEquals($name, $organization->getName());
        self::assertEquals('acme-inc', $organization->getAlias());
    }

    public function testValidationOfUniquenessOfAlias(): void
    {
        $owner = $this->sampleUser();
        $name = 'same';

        $this->createOrganization(
            Uuid::uuid4()->toString(),
            $owner->getId()->toString(),
            $name
        );

        $error = $this->createOrganization(
            Uuid::uuid4()->toString(),
            $owner->getId()->toString(),
            $name
        );

        self::assertFalse($error->isEmpty());
        self::assertEquals($error->get(), 'Organization name already exist');
    }

    public function testOwnerDoesNotExist(): void
    {
        $error = $this->createOrganization(
            $id = Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(), // bogus id
            'Failure Inc.'
        );

        $organization = $this->entityManager()
            ->getRepository(Organization::class)
            ->find($id);

        self::assertFalse($error->isEmpty());
        self::assertEquals($error->get(), 'User does not exist');

        self::assertNull($organization);
    }

    private function sampleUser(): User
    {
        /** @var User */
        $user = $this->entityManager()
            ->getRepository(User::class)
            ->findOneBy([]);

        return $user;
    }

    /**
     * @return Option<string>
     */
    private function createOrganization(string $id, string $ownerId, string $name): Option
    {
        $envelope = $this
            ->container()
            ->get(MessageBusInterface::class)
            ->dispatch(new CreateOrganization($id, $ownerId, $name));

        /** @var HandledStamp */
        $stamp = $envelope->last(HandledStamp::class);
        $error = $stamp->getResult();

        return $error;
    }
}
