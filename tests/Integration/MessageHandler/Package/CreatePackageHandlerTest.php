<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Package;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\Package\CreatePackage;
use Buddy\Repman\Query\User\PackageQuery\DbalPackageQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreatePackageHandlerTest extends IntegrationTestCase
{
    public function testSuccess(): void
    {
        $url = 'http://guthib.com/my/repo';

        $organizationId = $this->createOrganizationWithOwner();

        $this
            ->container()
            ->get(MessageBusInterface::class)
            ->dispatch(new CreatePackage(
                $id = Uuid::uuid4()->toString(),
                $organizationId,
                $url
            )
        );

        $package = $this
            ->container()
            ->get(DbalPackageQuery::class)
            ->getById($id)
            ->get();

        self::assertEquals($id, $package->id());
        self::assertEquals($url, $package->url());
    }

    public function testMissingOrganization(): void
    {
        self::expectException('Symfony\Component\Messenger\Exception\HandlerFailedException');
        self::expectExceptionMessage('Organization does not exist');

        $this
            ->container()
            ->get(MessageBusInterface::class)
            ->dispatch(new CreatePackage(
                $id = Uuid::uuid4()->toString(),
                Uuid::uuid4()->toString(), // random
                'test.com'
            )
        );
    }

    private function createOrganizationWithOwner(): string
    {
        $user = (new User($userId = Uuid::uuid4(), 'a@b.com', Uuid::uuid4()->toString(), []))->setPassword('pass');

        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        $this->createOrganization(
            $organizationId = Uuid::uuid4()->toString(),
            $userId->toString(),
            'Test organization'
        );

        return $organizationId;
    }
}
