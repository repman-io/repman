<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Proxy;

final class RemoveDist
{
    private string $proxy;
    private string $packageName;

    public function __construct(string $proxy, string $packageName)
    {
        $this->proxy = $proxy;
        $this->packageName = $packageName;
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
