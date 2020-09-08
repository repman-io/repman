<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Security;

use Buddy\Repman\Message\Security\SendScanResult;
use Buddy\Repman\MessageHandler\Security\SendScanResultHandler;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Query\User\PackageQuery\Filter;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class SendScanResultHandlerTest extends IntegrationTestCase
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
        $handler = $this->container()->get(SendScanResultHandler::class);
        $handler->__invoke(new SendScanResult(
            ['test@example.com'],
            'buddy',
            'buddy/repman',
            $this->packageId,
            []
        ));

        $package = $this
            ->container()
            ->get(PackageQuery::class)
            ->findAll($this->organizationId, new Filter())[0];

        self::assertEquals($package->scanResultStatus(), 'pending');
    }
}
