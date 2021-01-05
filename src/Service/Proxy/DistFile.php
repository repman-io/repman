<?php
/**
 * CRM PROJECT
 * THIS FILE IS A PART OF CRM PROJECT
 * CRM PROJECT IS PROPERTY OF Legal One GmbH.
 *
 * @copyright Copyright (c) 2020 Legal One GmbH (http://www.legal.one)
 */

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

class DistFile
{
    /**
     * @var resource
     */
    private $stream;

    private int $fileSize;

    /**
     * @param resource $stream
     */
    public function __construct($stream, int $fileSize)
    {
        $this->stream = $stream;
        $this->fileSize = $fileSize;
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
