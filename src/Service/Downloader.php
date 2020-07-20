<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Munus\Control\Option;

interface Downloader
{
    /**
     * todo: replace with getAsyncContents.
     *
     * @param string[] $headers
     *
     * @return Option<resource>
     */
    public function getContents(string $url, array $headers = [], callable $notFoundHandler = null): Option;

    /**
     * @param string[]                $headers
     * @param callable(resource):void $onFulfilled
     */
    public function getAsyncContents(string $url, array $headers, callable $onFulfilled): void;

    /**
     * @param callable(int):void $onFulfilled
     */
    public function getLastModified(string $url, callable $onFulfilled): void;

    public function run(): void;
}
