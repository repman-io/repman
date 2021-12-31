<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Buddy\Repman\Security\Model\Organization;
use Buddy\Repman\Security\OrganizationProvider;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

final class OrganizationProviderTest extends IntegrationTestCase
{
    public function testLoadCorrectOrganizationByToken(): void
    {
        $userId = $this->fixtures->createUser();
        $org1Id = $this->fixtures->createOrganization('buddy', $userId);
        $org2Id = $this->fixtures->createOrganization('packagist', $userId);
        $this->fixtures->createToken($org1Id, 'org1-token');
        $this->fixtures->createToken($org2Id, 'org2-token');

        $provider = $this->container()->get(OrganizationProvider::class);
        self::assertTrue($provider->supportsClass(Organization::class));

        /** @var Organization $organization */
        $organization = $this->container()->get(OrganizationProvider::class)->loadUserByIdentifier('org1-token');

        self::assertEquals('buddy', $organization->name());
        self::assertEquals($org1Id, $organization->id());
        self::assertEquals('org1-token', $organization->getPassword());
        self::assertEquals('', $organization->getSalt());
        self::assertEquals('buddy', $organization->getUserIdentifier());

        self::assertEquals($organization, $provider->refreshUser($organization));

        $this->expectException(UsernameNotFoundException::class);
        $provider->refreshUser(new Organization(Uuid::uuid4()->toString(), 'evil', 'evil', 'not-exist'));
    }
}
