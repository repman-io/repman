<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

final class Dist
{
    public function __construct(private readonly string $repo, private readonly string $package, private readonly string $version, private readonly string $ref, private readonly string $format)
    {
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
        if (str_contains($this->version, '/')) {
            return md5($this->version);
        }

        if ($this->version === 'dev-master') {
            return '9999999-dev';
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
