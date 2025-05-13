<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

class DistFile
{
    /**
     * @param resource $stream
     */
    public function __construct(private $stream, private readonly int $fileSize)
    {
    }

    /**
     * @return resource
     */
    public function stream()
    {
        return $this->stream;
    }

    public function fileSize(): int
    {
        return $this->fileSize;
    }
}
