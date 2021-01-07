<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Stream;
use Munus\Control\Option;

final class FakeDownloader implements Downloader
{
    // todo: remove this and allow only for in-memory content to explicit control
    private string $basePath;

    /**
     * @var mixed[]
     */
    private array $content = [];

    public function __construct(string $basePath = __DIR__.'/../Resources')
    {
        $this->basePath = $basePath;
    }

    /**
     * @param string[] $headers
     *
     * @return Option<resource>
     */
    public function getContents(string $url, array $headers = [], callable $notFoundHandler = null): Option
    {
        if (isset($this->content[$url])) {
            return Option::some(Stream::fromString($this->content[$url]['content']));
        }

        $path = $this->basePath.parse_url($url, PHP_URL_PATH);

        if (file_exists($path)) {
            return Option::some(Stream::fromString((string) file_get_contents($path)));
        }

        if (file_exists($url)) {
            return Option::some(Stream::fromString((string) file_get_contents($url)));
        }

        if (strstr($path, 'not-found') !== false && $notFoundHandler !== null) {
            $notFoundHandler();
        }

        return Option::none();
    }

    /**
     * @param callable(resource):void $onFulfilled
     */
    public function getAsyncContents(string $url, array $headers, callable $onFulfilled): void
    {
        if (isset($this->content[$url]) && $this->content[$url]['content'] !== null) {
            $onFulfilled(Stream::fromString($this->content[$url]['content']));
        }
    }

    /**
     * @param callable(int):void $onFulfilled
     */
    public function getLastModified(string $url, callable $onFulfilled): void
    {
        if (isset($this->content[$url])) {
            $timestamp = $this->content[$url]['timestamp'];
        }

        $path = $this->basePath.parse_url($url, PHP_URL_PATH);
        if (file_exists($path)) {
            $timestamp = (int) filemtime($path);
        }

        if (isset($timestamp)) {
            $onFulfilled($timestamp);
        }
    }

    public function addContent(string $url, ?string $content, int $timestamp = null): void
    {
        $this->content[$url] = [
            'timestamp' => $timestamp ?? time(),
            'content' => $content,
        ];
    }

    public function run(): void
    {
        // TODO: Implement run() method.
    }
}
