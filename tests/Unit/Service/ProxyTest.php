<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;

final class ProxyTest extends TestCase
{
    private Proxy $proxy;

    protected function setUp(): void
    {
        $this->proxy = new Proxy(
            'packagist.org',
            'https://packagist.org',
            new Filesystem(new Local(__DIR__.'/../../Resources')),
            new FakeDownloader()
        );
    }

    public function testPackageMetadata(): void
    {
        $metadata = $this->proxy->metadata('buddy-works/repman');

        self::assertTrue($metadata->isPresent());
    }

    public function testDownloadDistWhenNotExists(): void
    {
        $distPath = __DIR__.'/../../Resources/packagist.org/dist/buddy-works/repman/61e39aa8197cf1bc7fcb16a6f727b0c291bc9b76.zip';

        self::assertFileNotExists($distPath);
        $distribution = $this->proxy->distribution('buddy-works/repman', '1.2.3', '61e39aa8197cf1bc7fcb16a6f727b0c291bc9b76', 'zip');
        self::assertTrue($distribution->isPresent());

        fclose($distribution->get());
        unlink($distPath);
    }

    public function testDistRemove(): void
    {
        $distDir = __DIR__.'/../../Resources/packagist.org/dist/';
        mkdir($distDir.'vendor/package', 0777, true);
        file_put_contents($distDir.'vendor/package/some.zip', 'package-data');

        $this->proxy->removeDist('vendor/package');

        self::assertFileNotExists($distDir.'vendor/package/some.zip');
        self::assertDirectoryNotExists($distDir.'vendor/package');

        // test if remove package that not exist does not cause error
        $this->proxy->removeDist('vendor/package');
    }

    public function testPreventRemoveDist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->proxy->removeDist('');
    }
}
