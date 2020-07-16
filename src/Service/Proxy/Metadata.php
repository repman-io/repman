<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

final class Metadata
{
    private int $timestamp;

    /**
     * @var resource
     */
    private $stream;

    /**
     * @param resource $stream
     */
    public function __construct(int $timestamp, $stream)
    {
        $this->timestamp = $timestamp;
        $this->stream = $stream;
    }

    public static function fromString(string $string): self
    {
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Failed to open in-memory stream');
        }
        fwrite($stream, $string);
        rewind($stream);

        return new self(time(), $stream);
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return resource
     */
    public function stream()
    {
        return $this->stream;
    }
}
