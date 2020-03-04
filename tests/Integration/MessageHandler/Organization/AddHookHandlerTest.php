<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\AddHook;
use Buddy\Repman\MessageHandler\Organization\AddHookHandler;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;

final class AddHookHandlerTest extends IntegrationTestCase
{
    public function testAddHook(): void
    {
        $packageId = Uuid::uuid4();

        $this->fixtures->createPackage($packageId->toString());

        $handler = $this->container()->get(AddHookHandler::class);
        $handler(new AddHook(
            $packageId->toString(),
            'buddy/repman',
            'secret',
            'https://buddy.works'
        ));

        $package = $this->container()->get(PackageRepository::class)->getById($packageId);

        self::assertInstanceOf(\DateTimeImmutable::class, $package->webhookCreatedAt());
    }
}
