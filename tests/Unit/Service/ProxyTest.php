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
        $this->filesystem->write('packagist.org/p/buddy-works/repman.json', 'content');
        $this->filesystem->write('packagist.org/p2/buddy-works/repman.json', 'content', ['timestamp' => $oldTimestamp]);
        $this->filesystem->write('packagist.org/p2/buddy-works/old.json', 'content', ['timestamp' => $oldTimestamp]);
        $this->downloader->addContent('https://packagist.org/p/buddy-works/repman.json', 'legacy');
        $this->downloader->addContent('https://packagist.org/p2/buddy-works/repman.json', 'new');
        $this->downloader->addContent('https://packagist.org/p2/buddy-works/old.json', 'content', $oldTimestamp);

        /** @var Metadata $metadata */
        $metadata = $this->proxy->metadata('buddy-works/repman')->get();
        self::assertEquals($oldTimestamp, $metadata->timestamp());

        $this->proxy->syncMetadata();

        /** @var Metadata $metadata */
        $metadata = $this->proxy->metadata('buddy-works/repman')->get();
        self::assertTrue($metadata->timestamp() > $oldTimestamp);
        self::assertEquals('new', stream_get_contents($metadata->stream()));

        /** @var Metadata $metadata */
        $metadata = $this->proxy->metadata('buddy-works/old')->get();
        self::assertTrue($metadata->timestamp() > $oldTimestamp);

        self::assertTrue($this->filesystem->has('packagist.org/p/buddy-works/repman$c49fea7425fa7f8699897a97c159c6690267d9003bb78c53fafa8fc15c325d84.json'));
        self::assertFalse($this->filesystem->has('packagist.org/p2/buddy-works/repman$c49fea7425fa7f8699897a97c159c6690267d9003bb78c53fafa8fc15c325d84.json'));
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

    public function testUpdateLatestProviders(): void
    {
        $this->createMetadataFile('repman-io/example', '33f034b1', '2020-01-01 08:00:00');
        $this->createMetadataFile('repman-io/example', '7596c2e5', '2020-02-01 08:00:00');
        $this->createMetadataFile('repman-io/example', '8596c2e5', '2019-02-01 08:00:00');
        $this->createMetadataFile('repman-io/example', '90f50046', '2021-02-01 08:00:00');

        $this->proxy->updateLatestProviders();

        self::assertFalse($this->filesystem->has('packagist.org/p/repman-io/example$33f034b1.json'));
        self::assertFalse($this->filesystem->has('packagist.org/p/repman-io/example$7596c2e5.json'));
        self::assertFalse($this->filesystem->has('packagist.org/p/repman-io/example$8596c2e5.json'));
        self::assertTrue($this->filesystem->has('packagist.org/p/repman-io/example$90f50046.json'));
        self::assertTrue($this->filesystem->has('packagist.org/provider/provider-latest$9574a5410c31b1839e902dca97a3e0f892363864838fc5b522627c81300a60d3.json'));
    }

    private function createMetadataFile(string $package, string $hash, string $time): void
    {
        $this->filesystem->write('packagist.org/p/'.$package.'$'.$hash.'.json', 'content', ['timestamp' => strtotime($time)]);
    }
}
