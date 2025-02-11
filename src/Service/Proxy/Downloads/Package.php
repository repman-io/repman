<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy\Downloads;

final class Package
{
    public function __construct(private readonly string $name, private readonly string $version)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }
}
