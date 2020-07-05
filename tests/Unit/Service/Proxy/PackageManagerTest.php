<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Proxy;

use Buddy\Repman\Service\Proxy\PackageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;

class PackageManagerTest extends TestCase
{
    private string $basePath;
    private Filesystem $filesystem;
    private PackageManager $packageManager;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir().'/'.'repman';

        $this->filesystem = new Filesystem(new Local($this->basePath));
        $this->packageManager = new PackageManager($this->filesystem);
    }

    public function testItReturnsArrayOfPackages(): void
    {
        $this->createTempFile($packagePath = $this->basePath.'/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896.zip');
        $this->createTempFile($otherPath = $this->basePath.'/packagist.org/dist/buddy-works/other-package/0.1.2.0_f0c896.zip');

        $packages = $this->packageManager->packages('packagist.org');

        self::assertEquals(
            ['buddy-works/repman', 'buddy-works/other-package'],
            iterator_to_array($packages->reverse()->getIterator())
        );

        $this->filesystem->deleteDir('packagist.org');
    }

    public function testDistPackageRemove(): void
    {
        $this->createTempFile($packagePath = $this->basePath.'/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896.zip');
        $this->createTempFile($otherPath = $this->basePath.'/packagist.org/dist/buddy-works/other-package/0.1.2.0_f0c896.zip');
        $this->createTempFile($anotherProxyPath = $this->basePath.'/anotherproxy.org/dist/buddy-works/repman/0.1.2.0_f0c896.zip');

        self::assertFileExists($packagePath);
        self::assertFileExists($otherPath);
        self::assertFileExists($anotherProxyPath);

        $this->packageManager->remove('packagist.org', 'buddy-works/repman');

        self::assertFileNotExists($packagePath);
        self::assertFileExists($otherPath);
        self::assertFileExists($anotherProxyPath);

        $this->filesystem->deleteDir('packagist.org');
        $this->filesystem->deleteDir('anotherproxy.org');
    }

    public function testVendorDeletedIfNoPackages(): void
    {
        $this->createTempFile($packagePath = $this->basePath.'/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896.zip');

        self::assertFileExists($packagePath);

        $this->packageManager->remove('packagist.org', 'buddy-works/repman');

        self::assertFileNotExists($packagePath);
        self::assertDirectoryNotExists($this->basePath.'/packagist.org/dist/buddy-works');
    }

    private function createTempFile(string $path): void
    {
        $dirPath = dirname($path);
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        file_put_contents($path, 'content');
    }
}
