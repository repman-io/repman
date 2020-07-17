<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Downloader;

use Buddy\Repman\Kernel;
use Buddy\Repman\Service\Downloader;
use Munus\Control\Option;

final class NativeDownloader implements Downloader
{
    /**
     * @param string[] $headers
     *
     * @return Option<string>
     */
    public function getContents(string $url, array $headers = [], callable $notFoundHandler = null): Option
    {
        $retries = 3;
        do {
            $content = @file_get_contents($url, false, $this->createContext($headers));
            if ($content !== false) {
                return Option::some($content);
            }

            if (isset($http_response_header) && $this->getStatusCode($http_response_header) === 404 && $notFoundHandler !== null) {
                $notFoundHandler();
            }
            --$retries;
        } while ($retries > 0);

        return Option::none();
    }

    /**
     * @return Option<int>
     */
    public function getLastModified(string $url): Option
    {
        $headers = @get_headers($url, 1, stream_context_create(['http' => ['method' => 'HEAD']]));
        if (!is_array($headers) || !isset($headers['Last-Modified'])) {
            return Option::none();
        }

        return Option::some((int) strtotime($headers['Last-Modified']));
    }

    /**
     * @param string[] $headers
     *
     * @return resource
     */
    private function createContext(array $headers = [])
    {
        $phpVersion = 'PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;

        return stream_context_create([
            'http' => [
                'header' => array_merge([
                    sprintf(
                        'User-Agent: Repman/%s (%s; %s; %s)',
                        Kernel::REPMAN_VERSION,
                        php_uname('s'),
                        php_uname('r'),
                        $phpVersion
                    ),
                ], $headers),
                'follow_location' => 1,
                'max_redirects' => 20,
            ],
        ]);
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
