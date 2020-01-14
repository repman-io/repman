<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\Cache\InMemoryCache;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Dist\Storage\InMemoryStorage;
use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\MetadataProvider\SerializableMetadataProvider;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use Buddy\Repman\Tests\Doubles\FakeMetadataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

final class ProxyTest extends TestCase
{
    public function testPackageProvider(): void
    {
        $proxy = new Proxy('packagist.org', 'https://packagist.org', new SerializableMetadataProvider(new FakeDownloader(), new InMemoryCache()), new InMemoryStorage());
        $provider = $proxy->providerData('buddy-works/repman')->get();

        self::assertEquals('0.1.0', $provider['packages']['buddy-works/repman']['0.1.0']['version']);
    }

    public function testStorageNotForceToDownloadWhenDistExists(): void
    {
        /** @phpstan-var mixed $storage */
        $storage = $this->prophesize(Storage::class);
        $storage->has(Argument::type(Dist::class))->willReturn(true);
        $storage->download(Argument::cetera())->shouldNotBeCalled();
        $storage->filename(Argument::type(Dist::class))->willReturn(
            __DIR__.'/../../Resources/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896a759d4e2e1eff57978318e841911796305.zip'
        );

        $proxy = new Proxy('packagist.org', 'https://packagist.org', new FakeMetadataProvider(), $storage->reveal());

        self::assertStringContainsString(
            '0.1.2.0_f0c896a759d4e2e1eff57978318e841911796305.zip',
            $proxy->distFilename('buddy-works/repman', '0.1.2.0', 'f0c896a759d4e2e1eff57978318e841911796305', 'zip')->get()
        );
    }
}
