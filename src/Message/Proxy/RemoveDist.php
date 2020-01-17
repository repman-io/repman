<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Proxy;

final class RemoveDist
{
    private string $packageName;

    public function __construct(string $packageName)
    {
        $this->packageName = $packageName;
    }

    public function packageName(): string
    {
        return $this->packageName;
    }
}
