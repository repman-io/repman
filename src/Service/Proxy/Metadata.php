<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use Buddy\Repman\Service\Stream;
use function strlen;
use function time;

final class Metadata
{
    /**
     * @param resource $stream
     */
    public function __construct(private readonly int $timestamp, private $stream, private readonly int $contentSize, private readonly ?string $hash = null)
    {
    }

    public static function fromString(string $string): self
    {
        return new self(time(), Stream::fromString($string), strlen($string));
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
