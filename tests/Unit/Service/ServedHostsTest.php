<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Service\ServedHosts;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;

final class ServedHostsTest extends TestCase
{
    public function testDashSeparatorsPutEverythingNextToTheAppHost(): void
    {
        $hosts = new ServedHosts($this->organizations(), 'repman.example.com', '-', '-');

        self::assertTrue($hosts->serves('repman.example.com'));
        self::assertTrue($hosts->serves('repo-repman.example.com'));
        self::assertTrue($hosts->serves('acme-repo-repman.example.com'));
        self::assertFalse($hosts->serves('acme.repo.repman.example.com'));
        self::assertFalse($hosts->serves('other-repo-repman.example.com'));
    }

    public function testOrganizationOneLevelBelowTheAppHost(): void
    {
        $hosts = new ServedHosts($this->organizations(), 'repman.example.com', '.', '-');

        self::assertTrue($hosts->serves('repo.repman.example.com'));
        self::assertTrue($hosts->serves('acme-repo.repman.example.com'));
        self::assertFalse($hosts->serves('acme.repo.repman.example.com'));
    }

    private function organizations(): OrganizationQuery
    {
        $query = self::createStub(OrganizationQuery::class);
        $query->method('getByAlias')->willReturnCallback(
            fn (string $alias): Option => $alias === 'acme' ? Option::some(new Organization('id', 'Acme', 'acme', [], false)) : Option::none()
        );

        return $query;
    }
}
