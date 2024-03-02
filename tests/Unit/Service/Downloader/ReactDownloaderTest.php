<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Downloader;

use Buddy\Repman\Kernel;
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

    /**
     * @throws \ReflectionException
     */
    public function testStreamContext(): void
    {
        $_SERVER['HTTP_PROXY'] = $_SERVER['http_proxy'] = $_SERVER['HTTPS_PROXY'] = $_SERVER['https_proxy'] = null;

        $downloader = new ReactDownloader();
        $createContextMethod = new \ReflectionMethod(ReactDownloader::class, 'createContext');
        $createContextMethod->setAccessible(true);

        $context = $createContextMethod->invoke($downloader, 'https://repman.io');
        $options = stream_context_get_options($context);
        self::assertEquals(20, $options['http']['max_redirects']);
        self::assertEquals(1, $options['http']['follow_location']);
        self::assertEquals(
            sprintf(
            'User-Agent: Repman/%s (%s; %s; %s)',
            Kernel::REPMAN_VERSION,
                    php_uname('s'),
                    php_uname('r'),
                    'PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION
            ),
            $options['http']['header'][0]
        );
        self::assertArrayNotHasKey('proxy', $options['http']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testStreamContextHttpProxy(): void
    {
        $_SERVER['HTTP_PROXY'] = $_SERVER['http_proxy'] = $_SERVER['HTTPS_PROXY'] = $_SERVER['https_proxy'] = 'http://proxy.repman.io';

        $downloader = new ReactDownloader();
        $createContextMethod = new \ReflectionMethod(ReactDownloader::class, 'createContext');
        $createContextMethod->setAccessible(true);

        $context = $createContextMethod->invoke($downloader, 'https://repman.io');
        $options = stream_context_get_options($context);
        self::assertEquals(20, $options['http']['max_redirects']);
        self::assertEquals(1, $options['http']['follow_location']);
        self::assertEquals(
            sprintf(
                'User-Agent: Repman/%s (%s; %s; %s)',
                Kernel::REPMAN_VERSION,
                php_uname('s'),
                php_uname('r'),
                'PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION
            ),
            $options['http']['header'][0]
        );
        self::assertEquals('tcp://proxy.repman.io:80', $options['http']['proxy']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testStreamContextNoProxy(): void
    {
        $_SERVER['HTTP_PROXY'] = $_SERVER['http_proxy'] = $_SERVER['HTTPS_PROXY'] = $_SERVER['https_proxy'] = 'http://proxy.repman.io';
        $_SERVER['NO_PROXY'] = $_SERVER['no_proxy'] = '.repman.io';

        $downloader = new ReactDownloader();
        $createContextMethod = new \ReflectionMethod(ReactDownloader::class, 'createContext');
        $createContextMethod->setAccessible(true);

        $context = $createContextMethod->invoke($downloader, 'https://repman.io');
        $options = stream_context_get_options($context);
        self::assertEquals(20, $options['http']['max_redirects']);
        self::assertEquals(1, $options['http']['follow_location']);
        self::assertEquals(
            sprintf(
                'User-Agent: Repman/%s (%s; %s; %s)',
                Kernel::REPMAN_VERSION,
                php_uname('s'),
                php_uname('r'),
                'PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION
            ),
            $options['http']['header'][0]
        );
        self::assertArrayNotHasKey('proxy', $options['http']);
    }
}
