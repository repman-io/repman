<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

final class Dist
{
    private string $repo;
    private string $package;
    private string $version;
    private string $ref;
    private string $format;

    public function __construct(string $repo, string $package, string $version, string $ref, string $format)
    {
        $this->repo = $repo;
        $this->package = $package;
        $this->version = $version;
        $this->ref = $ref;
        $this->format = $format;
    }

    public function repo(): string
    {
        return $this->repo;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function version(): string
    {
        if (strpos($this->version, '/') !== false) {
            return md5($this->version);
        }

        return $this->version;
    }

    public function ref(): string
    {
        return $this->ref;
    }

    public function format(): string
    {
        return $this->format;
    }
}
