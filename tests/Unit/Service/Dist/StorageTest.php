<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Dist;

use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;

final class StorageTest extends TestCase
{
    private Storage $storage;

    public function setUp(): void
    {
        $this->storage = new Storage(
            new FakeDownloader(),
            new Filesystem(new MemoryAdapter())
        );
    }

    public function testFilename(): void
    {
        $dist = new Dist('repo', 'package', '1.1.0', '123456', 'zip');

        self::assertEquals('repo/dist/package/1.1.0_123456.zip', $this->storage->filename($dist));
    }

    public function testArtifactFilename(): void
    {
        $dist = new Dist('repo', 'package', '1.1.0', '', 'zip');
        self::assertEquals('repo/dist/package/1.1.0.zip', $this->storage->filename($dist));
    }
}
