<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use Buddy\Repman\Service\Stream;

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
        return new self(time(), Stream::fromString($string));
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
