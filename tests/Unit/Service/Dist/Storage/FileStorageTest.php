<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Dist\Storage;

use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage\FileStorage;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class FileStorageTest extends TestCase
{
    private FileStorage $storage;
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir().'/'.'repman';
        $this->storage = new FileStorage($this->basePath, new FakeDownloader());
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->basePath);
    }

    public function testDownloadPackage(): void
    {
        $packagePath = $this->basePath.'/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896.zip';
        self::assertFileNotExists($packagePath);

        $this->storage->download('https://some.domain/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896a759d4e2e1eff57978318e841911796305.zip', new Dist(
            'packagist.org',
            'buddy-works/repman',
            '0.1.2.0',
            'f0c896',
            'zip'
        ));

        self::assertFileExists($packagePath);
    }

    public function testHasPackage(): void
    {
        $this->createTempFile($packagePath = $this->basePath.'/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896.zip');

        self::assertTrue($this->storage->has(new Dist(
            'packagist.org',
            'buddy-works/repman',
            '0.1.2.0',
            'f0c896',
            'zip'
        )));

        self::assertFalse($this->storage->has(new Dist(
            'packagist.org',
            'buddy-works/repman',
            '1.1.2.0',
            'f0c896',
            'zip'
        )));
    }

    public function testDistPackageRemove(): void
    {
        $this->createTempFile($packagePath = $this->basePath.'/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896.zip');
        $this->createTempFile($otherPath = $this->basePath.'/packagist.org/dist/buddy-works/other-package/0.1.2.0_f0c896.zip');

        self::assertFileExists($packagePath);
        self::assertFileExists($otherPath);

        $this->storage->remove('buddy-works/repman');

        self::assertFileNotExists($packagePath);
        self::assertFileExists($otherPath);
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
