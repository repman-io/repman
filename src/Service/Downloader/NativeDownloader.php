<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Downloader;

use Buddy\Repman\Kernel;
use Buddy\Repman\Service\Downloader;
use Munus\Control\Option;

final class NativeDownloader implements Downloader
{
    /**
     * @return Option<string>
     */
    public function getContents(string $url): Option
    {
        $retries = 3;
        do {
            $content = @file_get_contents($url, false, $this->createContext());
            if ($content !== false) {
                return Option::some($content);
            }
            --$retries;
        } while ($retries > 0);

        return Option::none();
    }

    /**
     * @return resource
     */
    private function createContext()
    {
        $phpVersion = 'PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;

        return stream_context_create([
            'http' => [
                'header' => [
                    sprintf(
                        'User-Agent: Repman/%s (%s; %s; %s)',
                        Kernel::REPMAN_VERSION,
                        php_uname('s'),
                        php_uname('r'),
                        $phpVersion
                    ),
                ],
                'follow_location' => 1,
                'max_redirects' => 20,
            ],
        ]);
    }
}
