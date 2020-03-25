<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

final class AtomicFile
{
    public static function write(string $filename, string $content): void
    {
        $tempFilename = $filename.'.'.uniqid('temp', true);

        file_put_contents($tempFilename, $content);
        rename($tempFilename, $filename);
    }
}
