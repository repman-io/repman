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

    public static function fromString(string $string, string $streamName = 'php://memory'): self
    {
        $stream = @fopen($streamName, 'r+');
        if ($stream === false) {
            throw new \RuntimeException(sprintf('Failed to open %s stream', $streamName));
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
