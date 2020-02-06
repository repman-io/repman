<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class Package
{
    private string $id;
    private string $url;
    private string $name;
    private string $latestReleasedVersion;
    private \DateTimeImmutable $latestReleaseDate;
    private string $description;

    public function __construct(string $id, string $url, string $name, string $latestReleasedVersion, \DateTimeImmutable $latestReleaseDate, string $description)
    {
        $this->id = $id;
        $this->url = $url;
        $this->name = $name;
        $this->latestReleasedVersion = $latestReleasedVersion;
        $this->latestReleaseDate = $latestReleaseDate;
        $this->description = $description;
    }

    public function id(): string
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

    public function latestReleasedVersion(): string
    {
        return $this->latestReleasedVersion;
    }

    public function latestReleaseDate(): \DateTimeImmutable
    {
        return $this->latestReleaseDate;
    }

    public function description(): string
    {
        return $this->description;
    }
}
