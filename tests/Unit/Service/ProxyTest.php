<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Tests\Doubles\FakeRemoteFilesystem;
use PHPUnit\Framework\TestCase;

final class ProxyTest extends TestCase
{
    public function testPackageProvider(): void
    {
        $proxy = new Proxy('https://packagist.org', new FakeRemoteFilesystem());
        $provider = $proxy->provider('buddy-works/repman')->get();

        self::assertEquals('0.1.0', $provider['packages']['buddy-works/repman']['0.1.0']['version']);
    }
}
