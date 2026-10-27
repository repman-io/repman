<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Entity\Organization;

use Buddy\Repman\Entity\Organization\Package\Link;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

final class PackageLinkTest extends IntegrationTestCase
{
    public function testStoresSuggestDescriptionLongerThan255Characters(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->fixtures->createUser());
        $packageId = $this->fixtures->addPackage($organizationId, 'https://github.com/buddy-works/repman');
        $description = str_repeat('Long suggestion text. ', 20);
        $entityManager = $this->container()->get(EntityManagerInterface::class);
        $packages = $this->container()->get(PackageRepository::class);

        $package = $packages->getById(Uuid::fromString($packageId));
        $package->addLink(new Link(Uuid::uuid4(), $package, 'suggests', 'ext-foo', $description));
        $entityManager->flush();
        $entityManager->clear();
        $package = $packages->getById(Uuid::fromString($packageId));

        self::assertGreaterThan(255, mb_strlen($description));
        $link = $package->links()->first();
        self::assertInstanceOf(Link::class, $link);
        self::assertSame($description, $link->constraint());
    }
}
