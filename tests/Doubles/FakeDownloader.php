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

    public function getContents(string $url): Option
    {
        $path = $this->basePath.parse_url($url, PHP_URL_PATH);
        if (file_exists($path)) {
            return Option::some((string) file_get_contents($path));
        }

        return Option::none();
    }
}
