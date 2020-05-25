<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\SecurityChecker;

final class Package
{
    private string $name;
    private string $version;

    public function __construct(string $name, string $version)
    {
        $this->name = $name;
        $this->version = $version;
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
