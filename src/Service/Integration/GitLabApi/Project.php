<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\GitLabApi;

final class Project
{
    private int $id;
    private string $name;
    private string $url;

    public function __construct(int $id, string $name, string $url)
    {
        $this->id = $id;
        $this->name = $name;
        $this->url = $url;
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
