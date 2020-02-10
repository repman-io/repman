<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Token;
use Buddy\Repman\Entity\User;
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
        $package = new Package(Uuid::uuid4(), 'vcs', 'https://repman.buddy.works');

        $this->org->addPackage($package);
        $this->org->addPackage($package); // this should not throw exception

        $this->expectException(\RuntimeException::class);
        $package->setOrganization($this->org);
    }

    public function testRegenerateNonExistToken(): void
    {
        $this->org->regenerateToken('some-secret', 'new-secret');
        // exception should not be thrown
        self::assertTrue(true);
    }

    public function testRemoveNonExistToken(): void
    {
        $this->org->removeToken('some-secret');
        // exception should not be thrown
        self::assertTrue(true);
    }

    public function testRemoveNonExsitPackage(): void
    {
        $this->org->removePackage(Uuid::uuid4());
        // exception should not be thrown
        self::assertTrue(true);
    }
}
