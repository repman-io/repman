<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Token;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Tests\MotherObject\PackageMother;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class OrganizationTest extends TestCase
{
    private Organization $org;

    protected function setUp(): void
    {
        $this->org = new Organization(Uuid::uuid4(), new User(Uuid::uuid4(), 'admin@buddy.works', Uuid::uuid4()->toString(), []), 'Buddy', 'buddy');
    }

    public function testOrganizationAddSameToken(): void
    {
        $token = new Token('secret', 'prod');

        $this->org->addToken($token);
        $this->org->addToken($token); // this should not throw exception

        $this->expectException(\RuntimeException::class);
        $token->setOrganization($this->org);
    }

    public function testOrganizationAddSamePackage(): void
    {
        $package = PackageMother::some();

        $this->org->addPackage($package);
        $this->org->addPackage($package); // this should not throw exception

        $this->expectException(\RuntimeException::class);
        $package->setOrganization($this->org);
    }
}
