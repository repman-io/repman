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

    private int $contentSize;

    private ?string $hash;

    /**
     * @param resource $stream
     */
    public function __construct(int $timestamp, $stream, int $contentSize, ?string $hash = null)
    {
        $this->timestamp = $timestamp;
        $this->stream = $stream;
        $this->contentSize = $contentSize;
        $this->hash = $hash;
    }

    public static function fromString(string $string): self
    {
        return new self(\time(), Stream::fromString($string), \strlen($string));
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

    public function hash(): ?string
    {
        return $this->hash;
    }

    public function contentSize(): int
    {
        return $this->contentSize;
    }
}
