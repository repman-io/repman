<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Query\User\PackageQuery\DbalPackageQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;

final class AddPackageHandlerTest extends IntegrationTestCase
{
    public function testSuccess(): void
    {
        $url = 'http://guthib.com/my/repo';

        $organizationId = $this->fixtures->createOrganization('Buddy', $this->fixtures->createUser());

        $this->dispatchMessage(new AddPackage(
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
        self::expectExceptionMessage('Organization with id c5e33fc9-27b0-42e1-b8cc-49a7f79b49b2 not found.');

        $this->dispatchMessage(new AddPackage(
            Uuid::uuid4()->toString(),
            'c5e33fc9-27b0-42e1-b8cc-49a7f79b49b2',
            'test.com'
            )
        );
    }
}
