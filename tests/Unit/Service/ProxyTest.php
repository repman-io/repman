<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\Metadata;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;

final class ProxyTest extends TestCase
{
    private Proxy $proxy;
    private Filesystem $filesystem;
    private FakeDownloader $downloader;

    protected function setUp(): void
    {
        $this->proxy = new Proxy(
            'packagist.org',
            'https://packagist.org',
            $this->filesystem = new Filesystem(new MemoryAdapter()),
            $this->downloader = new FakeDownloader()
        );
    }

    public function testPackageMetadataDownload(): void
    {
        $this->downloader->addContent('https://packagist.org/p2/buddy-works/repman.json', 'metadata');

        $metadata = $this->proxy->metadata('buddy-works/repman');

        self::assertTrue($metadata->isPresent());
    }

    public function testDownloadDistWhenNotExists(): void
    {
        $this->filesystem->write('packagist.org/p2/buddy-works/repman.json', (string) file_get_contents(__DIR__.'/../../Resources/packagist.org/p2/buddy-works/repman.json'));
        self::assertFalse($this->filesystem->has('packagist.org/dist/buddy-works/repman/61e39aa8197cf1bc7fcb16a6f727b0c291bc9b76.zip'));
        $distribution = $this->proxy->distribution('buddy-works/repman', '1.2.3', '61e39aa8197cf1bc7fcb16a6f727b0c291bc9b76', 'zip');
        self::assertTrue($distribution->isPresent());
    }

    public function testDistRemove(): void
    {
        $this->filesystem->write('packagist.org/dist/vendor/package/some.zip', 'package-data');

        $this->proxy->removeDist('vendor/package');

        self::assertFalse($this->filesystem->has('packagist.org/dist/vendor/package'));

        // test if remove package that not exist does not cause error
        $this->proxy->removeDist('vendor/package');
    }

    public function testPreventRemoveDist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->proxy->removeDist('');
    }

    public function testSyncMetadata(): void
    {
        $oldTimestamp = strtotime('2019-01-01 08:00:00');
        $this->filesystem->write('packagist.org/dist/some.json', 'content'); // should be ignored
        $this->filesystem->write('packagist.org/p2/buddy-works/repman.json', 'content', ['timestamp' => $oldTimestamp]);
        $this->filesystem->write('packagist.org/p2/buddy-works/old.json', 'content', ['timestamp' => $oldTimestamp]);
        $this->downloader->addContent('https://packagist.org/p2/buddy-works/repman.json', 'new');
        $this->downloader->addContent('https://packagist.org/p2/buddy-works/old.json', 'content', $oldTimestamp);

        /** @var Metadata $metadata */
        $metadata = $this->proxy->metadata('buddy-works/repman')->get();
        self::assertEquals($oldTimestamp, $metadata->timestamp());

        $this->proxy->syncMetadata();

        /** @var Metadata $metadata */
        $metadata = $this->proxy->metadata('buddy-works/repman')->get();
        self::assertTrue($metadata->timestamp() > $oldTimestamp);
        self:self::assertEquals('new', stream_get_contents($metadata->stream()));

        /** @var Metadata $metadata */
        $metadata = $this->proxy->metadata('buddy-works/old')->get();
        self::assertEquals($oldTimestamp, $metadata->timestamp());
    }

    public function testIgnoreSyncIfCannotDownload(): void
    {
        $oldTimestamp = strtotime('2019-01-01 08:00:00');
        $this->filesystem->write('packagist.org/p2/buddy-works/repman.json', 'content', ['timestamp' => $oldTimestamp]);
        $this->downloader->addContent('https://packagist.org/p2/buddy-works/repman.json', null);

        $this->proxy->syncMetadata();

        /** @var Metadata $metadata */
        $metadata = $this->proxy->metadata('buddy-works/repman')->get();
        self::assertEquals($oldTimestamp, $metadata->timestamp());
    }

    public function testSyncLegacyMetadata(): void
    {
        $oldTimestamp = strtotime('2019-01-01 08:00:00');
        $this->filesystem->write('packagist.org/p/buddy-works/repman.json', 'content', ['timestamp' => $oldTimestamp]);
        $this->downloader->addContent('https://packagist.org/p/buddy-works/repman.json', 'new');

        /** @var Metadata $metadata */
        $metadata = $this->proxy->legacyMetadata('buddy-works/repman')->get();
        self::assertEquals($oldTimestamp, $metadata->timestamp());

        $this->proxy->syncMetadata();

        /** @var Metadata $metadata */
        $metadata = $this->proxy->legacyMetadata('buddy-works/repman')->get();
        self::assertTrue($metadata->timestamp() > $oldTimestamp);
    }
}
