<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\Cache\InMemoryCache;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Dist\Storage\InMemoryStorage;
use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\MetadataProvider\CacheableMetadataProvider;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use Buddy\Repman\Tests\Doubles\FakeMetadataProvider;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

final class ProxyTest extends TestCase
{
    public function testPackageProvider(): void
    {
        $proxy = new Proxy('packagist.org', 'https://packagist.org', new CacheableMetadataProvider(new FakeDownloader(), new InMemoryCache()), new InMemoryStorage());
        $provider = $proxy->providerData('buddy-works/repman')->get();

        self::assertEquals('0.1.0', $provider['packages']['buddy-works/repman']['0.1.0']['version']);
    }

    public function testPackageProviderFromCache(): void
    {
        $cache = new InMemoryCache();
        $cache->get('packagist.org/packages.json', function (): array {return ['metadata']; });
        $cache->get('packagist.org/p/buddy-works/repman', function (): array {return ['package-metadata']; });
        $proxy = new Proxy('packagist.org', 'https://packagist.org', new CacheableMetadataProvider(new FakeDownloader(), $cache), new InMemoryStorage());

        self::assertEquals(['package-metadata'], $proxy->providerData('buddy-works/repman')->get());
    }

    public function testStorageDownloadDistWhenNotExists(): void
    {
        $distFilepath = __DIR__.'/../../Resources/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896a759d4e2e1eff57978318e841911796305.zip';
        /** @phpstan-var mixed $storage */
        $storage = $this->prophesize(Storage::class);
        $storage->has(Argument::type(Dist::class))->willReturn(false);
        $storage->filename(Argument::type(Dist::class))->willReturn($distFilepath);
        $storage->download('https://api.github.com/repos/munusphp/munus/zipball/f0c896a759d4e2e1eff57978318e841911796305', Argument::type(Dist::class))
            ->shouldBeCalledOnce();

        $proxy = new Proxy('packagist.org', 'https://packagist.org', new CacheableMetadataProvider(new FakeDownloader(), new InMemoryCache()), $storage->reveal());

        self::assertStringContainsString(
            '0.1.2.0_f0c896a759d4e2e1eff57978318e841911796305.zip',
            $proxy->distFilename('buddy-works/repman', '0.1.2.0', 'f0c896a759d4e2e1eff57978318e841911796305', 'zip')->get()
        );
    }

    public function testReturnNoneWhenDistPackageNotExists(): void
    {
        /** @phpstan-var mixed $storage */
        $storage = $this->prophesize(Storage::class);
        $storage->has(Argument::type(Dist::class))->willReturn(false);
        $storage->filename(Argument::type(Dist::class))->willReturn('/not/exist');
        $proxy = new Proxy('packagist.org', 'https://packagist.org', new CacheableMetadataProvider(new FakeDownloader(), new InMemoryCache()), $storage->reveal());

        self::assertTrue(Option::none()->equals(
            $proxy->distFilename('not-exist-vendor/not-exist-package', '0.1.2.0', 'f0c896a759d4e2e1eff57978318e841911796305', 'zip')
        ));
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

    public function testStorageHandleDistWithSlashInVersion(): void
    {
        $distFilepath = __DIR__.'/../../Resources/packagist.org/dist/buddy-works/repman/0cdaa0ab95de9fcf94ad9b1d2f80e15d_e738ed3634a11f6b5e23aca3d1c3f9be4efd8cfb.zip';
        /** @phpstan-var mixed $storage */
        $storage = $this->prophesize(Storage::class);
        $storage->has(Argument::type(Dist::class))->willReturn(false);
        $storage->filename(Argument::type(Dist::class))->willReturn($distFilepath);
        $storage->download('https://api.github.com/repos/munusphp/munus/zipball/e738ed3634a11f6b5e23aca3d1c3f9be4efd8cfb', Argument::type(Dist::class))
            ->shouldBeCalledOnce();

        $proxy = new Proxy('packagist.org', 'https://packagist.org', new CacheableMetadataProvider(new FakeDownloader(), new InMemoryCache()), $storage->reveal());

        self::assertStringContainsString(
            '0cdaa0ab95de9fcf94ad9b1d2f80e15d_e738ed3634a11f6b5e23aca3d1c3f9be4efd8cfb.zip',
            $proxy->distFilename('buddy-works/repman', 'dev-feature/awesome', 'e738ed3634a11f6b5e23aca3d1c3f9be4efd8cfb', 'zip')->get()
        );
        self::assertEquals('0cdaa0ab95de9fcf94ad9b1d2f80e15d', (new Dist('repo', 'package', 'dev-feature/awesome', 'ref', 'format'))->version());
    }
}
