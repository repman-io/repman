<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Security;

use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\MessageHandler\Security\ScanPackageHandler;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Query\User\PackageQuery\Filter;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class ScanPackageHandlerTest extends IntegrationTestCase
{
    private string $organizationId;
    private string $packageId;

    protected function setUp(): void
    {
        parent::setUp();

        $userId = $this->fixtures->createUser();
        $this->organizationId = $this->fixtures->createOrganization('buddy', $userId);
        $this->packageId = $this->fixtures->addPackage($this->organizationId, 'https://buddy.works');
    }

    public function testScan(): void
    {
        $handler = $this->container()->get(ScanPackageHandler::class);
        $handler->__invoke(new ScanPackage($this->packageId));

        $package = $this
            ->container()
            ->get(PackageQuery::class)
            ->findAll($this->organizationId, new Filter())[0];

        self::assertEquals($package->scanResultStatus(), 'pending');
    }

    public function testHandlePackageNotFoundWithoutError(): void
    {
        $exception = null;
        try {
            $handler = $this->container()->get(ScanPackageHandler::class);
            $handler->__invoke(new ScanPackage('1a01fc33-5265-43b9-9482-84eddcf0216e'));
        } catch (\Exception $exception) {
        }

        self::assertNull($exception);
    }
}
