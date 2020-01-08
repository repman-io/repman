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
        $content = @file_get_contents($url);

        if ($content === false) {
            return Option::none();
        }

        return Option::some($content);
    }
}
