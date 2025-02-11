<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Repository;

use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class PackageRepositoryTest extends IntegrationTestCase
{
    public function testPackageExist(): void
    {
        $orgId = $this->fixtures->createOrganization('buddy', $this->fixtures->createUser());
        $packageId = $this->fixtures->addPackage($orgId, 'http://new.package');
        $this->fixtures->syncPackageWithData($packageId, 'buddy/new-package', 'desc', '1.0.0', new DateTimeImmutable());

        $repo = $this->container()->get(PackageRepository::class);

        $this->assertTrue($repo->packageExist('buddy/new-package', Uuid::fromString($orgId)));
    }
}
