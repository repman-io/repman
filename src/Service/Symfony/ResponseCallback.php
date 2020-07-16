<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

final class ResponseCallback
{
    /**
     * @param resource $stream
     */
    public static function fromStream($stream): callable
    {
        return function () use ($stream): void {
            /** @var resource $out */
            $out = fopen('php://output', 'wb');
            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);
        };
    }
}
