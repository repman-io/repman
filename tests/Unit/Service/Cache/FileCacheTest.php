<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Cache;

use Buddy\Repman\Service\Cache\FileCache;
use Buddy\Repman\Tests\Doubles\InMemoryExceptionHandler;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class FileCacheTest extends TestCase
{
    private FileCache $cache;
    private InMemoryExceptionHandler $exceptionHandler;
    private string $basePath;
    private string $packagesPath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir().'/repman';
        $this->exceptionHandler = new InMemoryExceptionHandler();
        $this->cache = new FileCache($this->basePath, $this->exceptionHandler);
        $this->packagesPath = $this->basePath.'/packagist/packages.json';
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->basePath);
    }

    public function testThrowExceptionWhenCacheDirIsNotWritable(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FileCache('/proc/new', new InMemoryExceptionHandler());
    }

    public function testCacheHitAndExists(): void
    {
        $content = '{"some":"json"}';
        @mkdir(dirname($this->packagesPath));
        file_put_contents($this->packagesPath, serialize($content));

        self::assertTrue(Option::some($content)->equals(
            $this->cache->get('packagist/packages.json', function (): void {
                throw new \RuntimeException('This should not happen');
            })
        ));
        self::assertTrue($this->cache->exists('packagist/packages.json'));
    }

    public function testHandleExceptionOnFailure(): void
    {
        $exception = new \LogicException('something goes wrong');

        $this->cache->get('some.file', function () use ($exception): void {
            throw $exception;
        });

        self::assertTrue($this->exceptionHandler->exist($exception));
    }

    public function testCacheFind(): void
    {
        $file = '/p/buddy-works/repman$d1392374.json';
        @mkdir(dirname($this->basePath.$file), 0777, true);
        file_put_contents($this->basePath.$file, 'a:1:{s:4:"some";s:4:"json";}');

        self::assertTrue(Option::some(['some' => 'json'])->equals($this->cache->find('/p/buddy-works/repman')));
        self::assertTrue(Option::none()->equals($this->cache->find('/path/to/not-exist-dir')));
        self::assertTrue(Option::none()->equals($this->cache->find('/p/buddy-works/missing-package')));
    }

    public function testCacheFindExpire(): void
    {
        $file = '/p/buddy-works/repman$d1392374.json';
        @mkdir(dirname($this->basePath.$file), 0777, true);
        file_put_contents($this->basePath.$file, 'a:1:{s:4:"some";s:4:"json";}');

        self::assertTrue(Option::none()->equals($this->cache->find('/p/buddy-works/repman', -2)));
    }

    public function testCacheHitExpire(): void
    {
        $cache = new FileCache(__DIR__.'/../../../Resources', new InMemoryExceptionHandler());

        self::assertTrue(Option::none()->equals($cache->get('packages.json', function (): void {
            // to prevent overwrite packages.json
            throw new \LogicException();
        }, 60)));
    }

    public function testCacheMiss(): void
    {
        $content = '{"some":"json"}';

        self::assertTrue(!file_exists($this->packagesPath));
        self::assertTrue(Option::some($content)->equals(
            $this->cache->get('packagist/packages.json', fn () => $content)
        ));
        self::assertTrue(file_exists($this->packagesPath));
    }

    public function testCacheRemoveByPattern(): void
    {
        $file = '/p/buddy-works/repman$d1392374.json';
        @mkdir(dirname($this->basePath.$file), 0777, true);
        file_put_contents($this->basePath.$file, '{}');

        $this->cache->removeOld($file);
        self::assertTrue(!file_exists($this->basePath.$file));
    }

    public function testCacheNotRemoveWhenDollarSignIsMissing(): void
    {
        $file = '/p/buddy-works/repman.json';
        @mkdir(dirname($this->basePath.$file), 0777, true);
        file_put_contents($this->basePath.$file, '{}');

        $this->cache->removeOld($file);
        self::assertTrue(file_exists($this->basePath.$file));
        @unlink($this->basePath.$file);
    }
}
