<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Proxy;

final class RemoveDist
{
    public function __construct(private readonly string $proxy, private readonly string $packageName)
    {
    }

    public function proxy(): string
    {
        return $this->proxy;
    }

    public function packageName(): string
    {
        return $this->packageName;
    }
}
