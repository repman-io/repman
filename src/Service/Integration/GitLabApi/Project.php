<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\GitLabApi;

final class Project
{
    public function __construct(private readonly int $id, private readonly string $name, private readonly string $url)
    {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function name(): string
    {
        return $this->name;
    }
}
