<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\RemoteFilesystem;

use Buddy\Repman\Service\RemoteFilesystem;
use Munus\Control\Option;

final class NativeRemoteFilesystem implements RemoteFilesystem
{
    /**
     * @return Option<string>
     */
    public function getContents(string $url): Option
    {
        $content = @file_get_contents($url, false, $this->createContext());

        if ($content === false) {
            return Option::none();
        }

        return Option::some($content);
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
                        'dev', //TODO replace with released version
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
