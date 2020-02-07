<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Security\Model\Organization;
use Buddy\Repman\Security\OrganizationProvider;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class OrganizationProviderTest extends IntegrationTestCase
{
    public function testLoadCorrectOrganizationByToken(): void
    {
        $this->dispatchMessage(new CreateUser($userId = '914e2dbb-d57e-4b0f-89fd-9c57f6ce464b', 'test@buddy.works', 'plain', 'token'));
        $this->createOrganization($org1Id = '090b3f25-e1cb-41d6-abad-5d310b4ce654', $userId, 'buddy');
        $this->createOrganization($org2Id = '61476501-612a-494e-8cc2-cd9a04cd2bc4', $userId, 'packagist');
        $this->container()->get(TokenGenerator::class)->setNextToken('org1-token');
        $this->dispatchMessage(new GenerateToken($org1Id, 'prod'));
        $this->container()->get(TokenGenerator::class)->setNextToken('org2-token');
        $this->dispatchMessage(new GenerateToken($org2Id, 'prod'));

        $provider = $this->container()->get(OrganizationProvider::class);
        self::assertTrue($provider->supportsClass(Organization::class));

        /** @var Organization $organization */
        $organization = $this->container()->get(OrganizationProvider::class)->loadUserByUsername('org1-token');

        self::assertEquals('buddy', $organization->name());
        self::assertEquals($org1Id, $organization->id());
        self::assertEquals('org1-token', $organization->token());
        self::assertEquals(null, $organization->getPassword());
        self::assertEquals('', $organization->getSalt());
        self::assertEquals('org1-token', $organization->getUsername());

        self::assertEquals($organization, $provider->refreshUser($organization));
    }
}
