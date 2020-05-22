<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\PackageQuery\DbalPackageQuery;
use Buddy\Repman\Service\PackageSynchronizer;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class SynchronizePackageHandlerTest extends IntegrationTestCase
{
    public function testSuccess(): void
    {
        $organizationId = $this->fixtures->createOrganization('Buddy', $this->fixtures->createUser());
        $packageId = $this->fixtures->addPackage($organizationId, 'https://github.com/buddy-works/repman', 'vcs');
        $this->container()->get(PackageSynchronizer::class)->setData(
            $name = 'buddy-works/repman',
            $description = 'Repman - PHP repository manager',
            $version = '2.0.0',
            $date = new \DateTimeImmutable()
        );

        $this->dispatchMessage(new SynchronizePackage($packageId));

        /** @var Package $package */
        $package = $this->container()->get(DbalPackageQuery::class)->getById($packageId)->get();

        self::assertEquals($name, $package->name());
        self::assertEquals($description, $package->description());
        self::assertEquals($version, $package->latestReleasedVersion());

        /** @var \DateTimeImmutable $releaseDate */
        $releaseDate = $package->latestReleaseDate();
        self::assertEquals($date->format('Y-m-d H:i:s'), $releaseDate->format('Y-m-d H:i:s'));
    }

    public function testHandlePackageNotFoundWithoutError(): void
    {
        $exception = null;
        try {
            $this->dispatchMessage(new SynchronizePackage('e0ea4d32-4144-4a67-9310-6dae483a6377'));
        } catch (\Exception $exception) {
        }

        self::assertNull($exception);
    }
}
