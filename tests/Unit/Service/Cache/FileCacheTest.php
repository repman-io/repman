<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Cache;

use Buddy\Repman\Service\Cache\FileCache;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;

final class FileCacheTest extends TestCase
{
    private FileCache $cache;
    private string $basePath;
    private string $packagesPath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir().'/'.'repman';
        $this->cache = new FileCache($this->basePath);
        $this->packagesPath = $this->basePath.'/packagist/packages.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->packagesPath);
        @rmdir(dirname($this->packagesPath));
    }

    public function testCacheHit(): void
    {
        $content = '{"some":"json"}';
        @mkdir(dirname($this->packagesPath));
        file_put_contents($this->packagesPath, serialize($content));

        self::assertTrue(Option::some($content)->equals(
            $this->cache->get('packagist/packages.json', function () {
                throw new \RuntimeException('This should not happen');
            })
        ));
    }

    public function testCacheHitExpire(): void
    {
        $cache = new FileCache(__DIR__.'/../../../Resources');

        self::assertTrue(Option::none()->equals($cache->get('packages.json', function () {
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
}
