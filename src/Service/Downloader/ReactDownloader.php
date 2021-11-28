<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Downloader;

use Buddy\Repman\Kernel;
use Buddy\Repman\Service\Downloader;
use Clue\React\Mq\Queue;
use Munus\Control\Option;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use React\Socket\Connector;

final class ReactDownloader implements Downloader
{
    private LoopInterface $loop;
    private Browser $browser;
    private Queue $queue;

    public function __construct()
    {
        $this->loop = Loop::get();
        $this->browser = new Browser($this->loop, new Connector($this->loop, ['timeout' => 10]));
        $this->queue = new Queue(100, null, function (string $type, string $url, array $headers = []): PromiseInterface {
            return $this->browser->{$type}($url, array_merge($headers, ['User-Agent' => $this->userAgent()]));
        });
    }

    /**
     * @param string[] $headers
     *
     * @return Option<resource>
     */
    public function getContents(string $url, array $headers = [], callable $notFoundHandler = null): Option
    {
        $retries = 3;
        do {
            $stream = @fopen($url, 'r', false, $this->createContext($headers));
            if ($stream !== false) {
                return Option::some($stream);
            }

            if (isset($http_response_header) && $this->getStatusCode($http_response_header) === 404 && $notFoundHandler !== null) {
                $notFoundHandler();
            }
            --$retries;
        } while ($retries > 0);

        return Option::none();
    }

    public function getAsyncContents(string $url, array $headers, callable $onFulfilled): void
    {
        ($this->queue)('get', $url, $headers)
            ->then(function (ResponseInterface $response) use ($onFulfilled): void {
                $stream = $response->getBody()->detach();
                if (!is_resource($stream)) {
                    return;
                }
                $onFulfilled($stream);
            });
    }

    /**
     * @param callable(int):void $onFulfilled
     */
    public function getLastModified(string $url, callable $onFulfilled): void
    {
        ($this->queue)('head', $url)->then(function (ResponseInterface $response) use ($onFulfilled): void {
            $lastModified = $response->getHeader('Last-Modified');
            if ($lastModified !== []) {
                $onFulfilled((int) strtotime($lastModified[0]));
            }
        });
    }

    public function run(): void
    {
        $this->loop->run();
    }

    /**
     * @param string[] $headers
     *
     * @return resource
     */
    private function createContext(array $headers = [])
    {
        return stream_context_create([
            'http' => [
                'header' => array_merge([sprintf('User-Agent: %s', $this->userAgent())], $headers),
                'follow_location' => 1,
                'max_redirects' => 20,
            ],
        ]);
    }

    private function userAgent(): string
    {
        return sprintf(
            'Repman/%s (%s; %s; %s)',
            Kernel::REPMAN_VERSION,
            php_uname('s'),
            php_uname('r'),
            'PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION
        );
    }

    /**
     * @param mixed[] $headers
     */
    private function getStatusCode(array $headers): int
    {
        preg_match('{HTTP\/\S*\s(\d{3})}', $headers[0], $match);

        return (int) $match[1];
    }
}
