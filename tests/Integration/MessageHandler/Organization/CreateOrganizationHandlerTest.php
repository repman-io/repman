<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;

final class CreateOrganizationHandlerTest extends IntegrationTestCase
{
    public function testSuccess(): void
    {
        $owner = $this->sampleUser();
        $name = 'Acme Inc.';

        $this->createOrganization(
            $id = Uuid::uuid4()->toString(),
            $owner->id()->toString(),
            $name
        );

        // TODO: replace with query
        $organization = $this->entityManager()
            ->getRepository(Organization::class)
            ->find($id);

        self::assertInstanceOf(Organization::class, $organization);
        self::assertEquals($id, $organization->id()->toString());

        // check associations
        /** @var Organization $added */
        $added = $owner->getOrganizations()->first();
        self::assertEquals($added->id(), $organization->id());
        self::assertEquals($owner->id(), $organization->owner()->id());

        // check fields
        self::assertEquals($name, $organization->name());
        self::assertEquals('acme-inc', $organization->alias());
    }

    public function testOwnerDoesNotExist(): void
    {
        self::expectException('Symfony\Component\Messenger\Exception\HandlerFailedException');
        self::expectExceptionMessage('User does not exist');

        $this->createOrganization(
            $id = Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(), // bogus id
            'Failure Inc.'
        );
    }

    private function sampleUser(): User
    {
        // TODO: replace with dispatch message
        $user = (new User(Uuid::uuid4(), 'a@b.com', Uuid::uuid4()->toString(), []))->setPassword('pass');

        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }
}
