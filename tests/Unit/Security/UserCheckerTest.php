<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Security;

use Buddy\Repman\Security\Model\Organization;
use Buddy\Repman\Security\UserChecker;
use PHPUnit\Framework\TestCase;

final class UserCheckerTest extends TestCase
{
    public function testCheckerOnlyForUserInstance(): void
    {
        $checker = new UserChecker();
        $organization = new Organization('a09aedb5-e2cc-4364-a978-ad58345314b3', 'buddy', 'buddy', 'token');

        $checker->checkPostAuth($organization);

        // exception was not thrown
        self::assertEquals('buddy', $organization->name());
    }
}
