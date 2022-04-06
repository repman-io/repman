<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Repository;

use InvalidArgumentException;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class OrganizationRepositoryTest extends IntegrationTestCase
{
    public function testMissingInvitationToken(): void
    {
        $repo = $this->container()->get(OrganizationRepository::class);

        $this->expectException(InvalidArgumentException::class);
        $repo->getByInvitationToken('not-exist');
    }
}
