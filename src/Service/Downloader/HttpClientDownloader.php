<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Downloader;

use Buddy\Repman\Kernel;
use Buddy\Repman\Service\Downloader;
use Munus\Control\Option;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Response\StreamWrapper;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Symfony HttpClient does the retries (rate limits and server errors, honouring Retry-After), the
 * redirects and the concurrency of the asynchronous requests; local paths (the dists of artifact and
 * path packages) are opened directly.
 */
final class HttpClientDownloader implements Downloader
{
    private const RETRIES = 2;

    private HttpClientInterface $client;

    /**
     * @var \SplObjectStorage<ResponseInterface, callable(ResponseInterface):void>
     */
    private \SplObjectStorage $pending;

    public function __construct(?HttpClientInterface $client = null)
    {
        $this->client = new RetryableHttpClient(
            ($client ?? HttpClient::create())->withOptions([
                'headers' => ['User-Agent' => $this->userAgent()],
                'max_redirects' => 20,
                'timeout' => 30,
            ]),
            new GenericRetryStrategy(GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES, 250, 2.0, 5000),
            self::RETRIES
        );
        $this->pending = new \SplObjectStorage();
    }

    /**
     * @param string[] $headers
     *
     * @return Option<resource>
     */
    public function getContents(string $url, array $headers = [], ?callable $notFoundHandler = null): Option
    {
        if (!self::isHttp($url)) {
            $stream = @fopen($url, 'r');

            return $stream !== false ? Option::some($stream) : Option::none();
        }

        try {
            $response = $this->client->request('GET', $url, ['headers' => $headers]);
            $status = $response->getStatusCode();
        } catch (ExceptionInterface) {
            return Option::none();
        }

        if ($status === 404 && $notFoundHandler !== null) {
            $notFoundHandler();
        }

        return $status >= 200 && $status < 300 ? Option::some(StreamWrapper::createResource($response, $this->client)) : Option::none();
    }

    public function getAsyncContents(string $url, array $headers, callable $onFulfilled): void
    {
        $this->queue($this->client->request('GET', $url, ['headers' => $headers]), function (ResponseInterface $response) use ($onFulfilled): void {
            // 304 Not Modified to a conditional request: what is stored is current
            if ($response->getStatusCode() !== 200) {
                return;
            }
            $stream = fopen('php://temp', 'r+b');
            if ($stream === false) {
                return;
            }
            fwrite($stream, $response->getContent());
            rewind($stream);
            $onFulfilled($stream, $response->getHeaders()['last-modified'][0] ?? '');
        });
    }

    /**
     * @param callable(int):void $onFulfilled
     */
    public function getLastModified(string $url, callable $onFulfilled): void
    {
        $this->queue($this->client->request('HEAD', $url), function (ResponseInterface $response) use ($onFulfilled): void {
            if ($response->getStatusCode() >= 300) {
                return;
            }
            $lastModified = $response->getHeaders(false)['last-modified'][0] ?? null;
            if ($lastModified !== null) {
                $onFulfilled((int) strtotime($lastModified));
            }
        });
    }

    /**
     * Waits for every queued request, running the callbacks as they complete; a failed request keeps
     * what is stored, so its error is dropped.
     */
    public function run(): void
    {
        while ($this->pending->count() > 0) {
            $responses = iterator_to_array($this->pending);
            foreach ($this->client->stream($responses) as $response => $chunk) {
                try {
                    if ($chunk->isFirst()) {
                        // reading the status here is what keeps an error status from being thrown
                        $response->getStatusCode();
                    }
                    if (!$chunk->isLast()) {
                        continue;
                    }
                    $callback = $this->pending[$response];
                    $this->pending->detach($response);
                    $callback($response);
                } catch (ExceptionInterface) {
                    $this->pending->detach($response);
                }
            }
        }
    }

    /**
     * @param callable(ResponseInterface):void $callback
     */
    private function queue(ResponseInterface $response, callable $callback): void
    {
        $this->pending[$response] = $callback;
    }

    private static function isHttp(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
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
}
