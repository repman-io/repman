<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Downloader;
use Munus\Control\Option;

final class FakeDownloader implements Downloader
{
    private string $basePath;

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
        $path = $this->basePath.parse_url($url, PHP_URL_PATH);

        if (file_exists($path)) {
            return Option::some((string) file_get_contents($path));
        }

        if (strstr($path, 'not-found') !== false && $notFoundHandler !== null) {
            $notFoundHandler();
        }

        return Option::none();
    }
}
