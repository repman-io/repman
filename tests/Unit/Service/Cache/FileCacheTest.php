<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Cache;

use Buddy\Repman\Service\Cache\FileCache;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;

final class FileCacheTest extends TestCase
{
    private FileCache $cache;
    private string $packagesPath;

    protected function setUp(): void
    {
        $basePath = sys_get_temp_dir().'/'.'repman';
        $this->cache = new FileCache($basePath);
        $this->packagesPath = $basePath.'/packagist/packages.json';
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
        file_put_contents($this->packagesPath, $content);

        self::assertTrue(Option::some($content)->equals(
            $this->cache->get('packagist/packages.json', function () {
                throw new \RuntimeException('This should not happen');
            })
        ));
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

    public function testCacheDelete(): void
    {
        @mkdir(dirname($this->packagesPath));
        file_put_contents($this->packagesPath, '{"some":"json"}');

        $this->cache->delete('packagist/packages.json');
        self::assertTrue(!file_exists($this->packagesPath));
    }
}
