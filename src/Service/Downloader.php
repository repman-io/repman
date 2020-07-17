<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Munus\Control\Option;

interface Downloader
{
    /**
     * @param string[] $headers
     *
     * @return Option<string>
     */
    public function getContents(string $url, array $headers = [], callable $notFoundHandler = null): Option;

    /**
     * @return Option<int>
     */
    public function getLastModified(string $url): Option;
}
