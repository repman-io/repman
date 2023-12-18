<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Downloader;

use Buddy\Repman\Service\Downloader\ReactDownloader;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;

final class ReactDownloaderTest extends TestCase
{
    public function testSuccessDownload(): void
    {
        $packages = __DIR__.'/../../../Resources/packages.json';

        self::assertIsResource((new ReactDownloader())->getContents($packages)->getOrNull());
    }

    public function testFailedDownload(): void
    {
        self::assertTrue(Option::none()->equals(
            (new ReactDownloader())->getContents('/tmp/not-exists')
        ));
    }

    public function testNotFoundHandler(): void
    {
        $this->expectException(\LogicException::class);

        (new ReactDownloader())->getContents('https://repman.io/not-exist', [], function (): void {throw new \LogicException('Not found'); });
    }

    public function testLastModified(): void
    {
        $downloader = new ReactDownloader();
        $downloader->getLastModified('https://repman.io', function (int $timestamp): void {
            self::assertTrue($timestamp > 0);
        });
        $downloader->getLastModified('/tmp/not-exists', function (int $timestamp): void {
            throw new \LogicException('Should not happen');
        });
        $downloader->run();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAsyncContent(): void
    {
        $downloader = new ReactDownloader();
        $downloader->getAsyncContents('https://repman.io', [], function ($stream): void {
            $meta = stream_get_meta_data($stream);
            self::assertTrue($meta['uri'] === 'https://repman.io');
        });
        $downloader->getAsyncContents('/tmp/not-exists', [], function ($stream): void {
            throw new \LogicException('Should not happen');
        });
        $downloader->run();
    }
}
