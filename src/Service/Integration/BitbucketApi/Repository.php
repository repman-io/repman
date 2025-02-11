<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\BitbucketApi;

final class Repository
{
    public function __construct(private readonly string $uuid, private readonly string $name, private readonly string $url)
    {
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function url(): string
    {
        return $this->url;
    }
}
