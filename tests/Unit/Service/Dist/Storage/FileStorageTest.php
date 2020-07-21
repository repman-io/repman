<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Dist\Storage;

use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage\FileStorage;
use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        $this->storage->download('https://some.domain/packagist.org/dist/buddy-works/repman/f0c896a759d4e2e1eff57978318e841911796305.zip', new Dist(
            'packagist.org',
            'buddy-works/repman',
            '0.1.2.0',
            'f0c896',
            'zip'
        ));

        self::assertFileExists($packagePath);
    }

    public function testNotDownloadWhenPackageExist(): void
    {
        $packagePath = $this->basePath.'/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896.zip';
        $this->createTempFile($packagePath);

        $downloader = $this->createMock(Downloader::class);
        $downloader->expects(self::never())->method('getContents');

        $storage = new FileStorage($this->basePath, $downloader);
        $storage->download('https://some.domain/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896a759d4e2e1eff57978318e841911796305.zip', new Dist(
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

    public function testSize(): void
    {
        $this->createTempFile($packagePath = $this->basePath.'/packagist.org/dist/buddy-works/repman/0.1.2.0_f0c896.zip');

        self::assertEquals(7, $this->storage->size(new Dist(
            'packagist.org',
            'buddy-works/repman',
            '0.1.2.0',
            'f0c896',
            'zip'
        )));
    }

    public function testThrowHttpNotFoundExceptionWhenFileNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->storage->download('https://some.domain/packagist.org/dist/not-found', new Dist(
            'packagist.org',
            'buddy-works/repman',
            '0.1.2.0',
            'f0c896',
            'zip'
        ));
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
