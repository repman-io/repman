<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Buddy\Repman\Security\UserProvider;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class UserProviderTest extends IntegrationTestCase
{
    public function testUpgradeUserPassword(): void
    {
        $this->fixtures->createUser($email = 'test@buddy.works');
        $provider = $this->container()->get(UserProvider::class);

        $user = $provider->loadUserByIdentifier($email);
        $provider->upgradePassword($user, 'new-encoded');

        $user = $provider->loadUserByIdentifier($email);

        self::assertEquals('new-encoded', $user->getPassword());
    }
}
