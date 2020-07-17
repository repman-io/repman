<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Downloader;
use Munus\Control\Option;

final class FakeDownloader implements Downloader
{
    // todo: remove this and allow only for in-memory content to explicit control
    private string $basePath;

    /**
     * @var mixed[]
     */
    private array $content = [];

    public function __construct()
    {
        $this->basePath = __DIR__.'/../Resources';
    }

    /**
     * @param string[] $headers
     *
     * @return Option<string>
     */
    public function getContents(string $url, array $headers = [], callable $notFoundHandler = null): Option
    {
        if (isset($this->content[$url])) {
            return Option::some($this->content[$url]['content']);
        }

        $path = $this->basePath.parse_url($url, PHP_URL_PATH);

        if (file_exists($path)) {
            return Option::some((string) file_get_contents($path));
        }

        if (strstr($path, 'not-found') !== false && $notFoundHandler !== null) {
            $notFoundHandler();
        }

        return Option::none();
    }

    /**
     * @return Option<int>
     */
    public function getLastModified(string $url): Option
    {
        if (isset($this->content[$url])) {
            return Option::some($this->content[$url]['timestamp']);
        }

        $path = $this->basePath.parse_url($url, PHP_URL_PATH);
        if (file_exists($path)) {
            return Option::some((int) filemtime($path));
        }

        return Option::none();
    }

    public function addContent(string $url, ?string $content, int $timestamp = null): void
    {
        $this->content[$url] = [
            'timestamp' => $timestamp ?? time(),
            'content' => $content,
        ];
    }
}
