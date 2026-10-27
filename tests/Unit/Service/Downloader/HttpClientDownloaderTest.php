<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Downloader;

use Buddy\Repman\Service\Downloader\HttpClientDownloader;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Runs against a local `php -S` server, so it neither needs the network nor depends on a remote site.
 */
final class HttpClientDownloaderTest extends TestCase
{
    private static Process $server;
    private static string $baseUrl;
    private static string $stateDir;

    public static function setUpBeforeClass(): void
    {
        self::$stateDir = sys_get_temp_dir().'/repman-downloader-'.uniqid('', true);
        mkdir(self::$stateDir);
        $router = self::$stateDir.'/router.php';
        file_put_contents($router, <<<'PHP'
            <?php
            $state = __DIR__.'/hits-'.trim($_SERVER['REQUEST_URI'], '/');
            file_put_contents($state, (string) ((int) @file_get_contents($state) + 1));
            switch ($_SERVER['REQUEST_URI']) {
                case '/ok':
                    header('Last-Modified: Wed, 21 Oct 2015 07:28:00 GMT');
                    echo '{"ok":true}';
                    break;
                case '/missing':
                    http_response_code(404);
                    break;
                case '/forbidden':
                    http_response_code(403);
                    break;
                case '/redirect-to-missing':
                    header('Location: /missing', true, 302);
                    break;
                case '/rate-limited-once':
                    if ((int) file_get_contents($state) === 1) {
                        http_response_code(429);
                        header('Retry-After: 0');
                        break;
                    }
                    echo 'second time lucky';
                    break;
            }
            PHP);

        $port = self::freePort();
        self::$baseUrl = 'http://127.0.0.1:'.$port;
        self::$server = new Process([\PHP_BINARY, '-S', '127.0.0.1:'.$port, $router]);
        self::$server->start();
        for ($i = 0; $i < 50 && @fsockopen('127.0.0.1', $port) === false; ++$i) {
            usleep(100_000);
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
        (new Filesystem())->remove(self::$stateDir);
    }

    public function testSuccessDownload(): void
    {
        $packages = __DIR__.'/../../../Resources/packages.json';

        self::assertIsResource((new HttpClientDownloader())->getContents($packages)->getOrNull());
    }

    public function testDownloadOverHttp(): void
    {
        $stream = (new HttpClientDownloader())->getContents(self::$baseUrl.'/ok')->getOrNull();

        self::assertIsResource($stream);
        self::assertSame('{"ok":true}', stream_get_contents($stream));
    }

    public function testFailedDownload(): void
    {
        self::assertTrue(Option::none()->equals(
            (new HttpClientDownloader())->getContents('/tmp/not-exists')
        ));
    }

    public function testNotFoundHandler(): void
    {
        $this->expectException(\LogicException::class);

        (new HttpClientDownloader())->getContents(self::$baseUrl.'/missing', [], function (): void {throw new \LogicException('Not found'); });
    }

    public function testNotFoundHandlerAfterRedirect(): void
    {
        $this->expectException(\LogicException::class);

        (new HttpClientDownloader())->getContents(self::$baseUrl.'/redirect-to-missing', [], function (): void {throw new \LogicException('Not found'); });
    }

    public function testClientErrorIsNotRetried(): void
    {
        self::assertTrue((new HttpClientDownloader())->getContents(self::$baseUrl.'/forbidden')->isEmpty());
        self::assertSame('1', file_get_contents(self::$stateDir.'/hits-forbidden'));
    }

    public function testRateLimitIsRetried(): void
    {
        $stream = (new HttpClientDownloader())->getContents(self::$baseUrl.'/rate-limited-once')->getOrNull();

        self::assertIsResource($stream);
        self::assertSame('second time lucky', stream_get_contents($stream));
        self::assertSame('2', file_get_contents(self::$stateDir.'/hits-rate-limited-once'));
    }

    public function testLastModified(): void
    {
        $timestamps = [];
        $downloader = new HttpClientDownloader();
        $downloader->getLastModified(self::$baseUrl.'/ok', function (int $timestamp) use (&$timestamps): void {
            $timestamps[] = $timestamp;
        });
        $downloader->getLastModified(self::$baseUrl.'/missing', function (int $timestamp): void {
            throw new \LogicException('Should not happen');
        });
        $downloader->run();

        self::assertSame([1445412480], $timestamps);
    }

    public function testAsyncContent(): void
    {
        $contents = [];
        $downloader = new HttpClientDownloader();
        $downloader->getAsyncContents(self::$baseUrl.'/ok', [], function ($stream, string $lastModified) use (&$contents): void {
            $contents[] = stream_get_contents($stream);
            $contents[] = $lastModified;
        });
        $downloader->getAsyncContents(self::$baseUrl.'/missing', [], function ($stream): void {
            throw new \LogicException('Should not happen');
        });
        $downloader->run();

        self::assertSame(['{"ok":true}', 'Wed, 21 Oct 2015 07:28:00 GMT'], $contents);
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($socket);
        $address = (string) stream_socket_get_name($socket, false);
        $port = (int) substr($address, (int) strrpos($address, ':') + 1);
        fclose($socket);

        return $port;
    }
}
