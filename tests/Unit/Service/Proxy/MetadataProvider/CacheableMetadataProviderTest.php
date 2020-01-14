<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Proxy\MetadataProvider;

use Buddy\Repman\Service\Cache;
use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Proxy\MetadataProvider\CacheableMetadataProvider;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

final class CacheableMetadataProviderTest extends TestCase
{
    /**
     * @var mixed
     */
    private $cache;

    /**
     * @var mixed
     */
    private $downloader;

    private CacheableMetadataProvider $provider;

    public function testFromPathWithoutCache(): void
    {
        $this->cache->exists(Argument::cetera())->willReturn(false);

        self::assertTrue(Option::none()->equals(
            $this->provider->fromPath('buddy-works/repman', 'https://repman.buddy.works', 60)
        ));
    }

    public function testFromPathWithCache(): void
    {
        $metadata = ['metadata'];
        $this->cache->exists(Argument::cetera())->willReturn(true);
        $this->cache->find(Argument::type('string'))->willReturn(Option::some($metadata));

        self::assertTrue(Option::some($metadata)->equals(
            $this->provider->fromPath('buddy-works/repman', 'https://repman.buddy.works', 60)
        ));
    }

    protected function setUp(): void
    {
        $this->cache = $this->prophesize(Cache::class);
        $this->downloader = $this->prophesize(Downloader::class);

        /** @var Downloader $downloader */
        $downloader = $this->downloader->reveal();
        /** @var Cache $cache */
        $cache = $this->cache->reveal();

        $this->provider = new CacheableMetadataProvider($downloader, $cache);
    }
}
