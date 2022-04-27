<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

final class Stream
{
    /**
     * @return resource
     */
    public static function fromString(string $string, string $streamName = 'php://memory')
    {
        $stream = @fopen($streamName, 'r+');
        if ($stream === false) {
            throw new \RuntimeException(sprintf('Failed to open %s stream', $streamName));
        }
        fwrite($stream, $string);
        rewind($stream);

        return $stream;
    }
}
