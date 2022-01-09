<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Security\Model;

use Buddy\Repman\Security\Model\Organization;
use PHPUnit\Framework\TestCase;

final class OrganizationTest extends TestCase
{
    public function testGetUsername(): void
    {
        $organization = new Organization('foo', 'bar', 'baz', 'quz');

        self::assertSame('baz', $organization->getUsername());
    }
}
