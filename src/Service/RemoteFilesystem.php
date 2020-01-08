<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Munus\Control\Option;

interface RemoteFilesystem
{
    /**
     * @return Option<string>
     */
    public function getContents(string $url): Option;
}
