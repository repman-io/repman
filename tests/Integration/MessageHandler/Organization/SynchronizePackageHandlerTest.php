<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\MessageHandler\Organization\SynchronizePackageHandler;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;

final class SynchronizePackageHandlerTest extends IntegrationTestCase
{
    public function testSuccess(): void
    {
        $organizationId = $this->fixtures->createOrganization('Buddy', $this->fixtures->createUser());
        $handler = $this->container()->get(SynchronizePackageHandler::class);
        $handler(new SynchronizePackage(
                $id = Uuid::uuid4()->toString()
            )
        );

        // TODO: write test for synchronzie
        self::assertTrue(true);
    }
}
