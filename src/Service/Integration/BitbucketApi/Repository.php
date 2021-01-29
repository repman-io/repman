<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\BitbucketApi;

final class Repository
{
    private string $uuid;
    private string $name;
    private string $url;

    public function __construct(string $uuid, string $name, string $url)
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->url = $url;
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
