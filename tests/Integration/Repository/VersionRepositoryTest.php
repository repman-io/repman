<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Repository;

use Buddy\Repman\Repository\VersionRepository;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;

final class VersionRepositoryTest extends IntegrationTestCase
{
    public function testRemoveVersionThatDoesNotExist(): void
    {
        $repo = $this->container()->get(VersionRepository::class);
        $id = Uuid::uuid4();

        $exception = null;
        try {
            $repo->remove($id);
        } catch (\Exception $exception) {
        }

        self::assertInstanceOf(\InvalidArgumentException::class, $exception);
        self::assertEquals($exception->getMessage(), "Version {$id->toString()} not found.");
    }
}
