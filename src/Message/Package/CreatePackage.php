<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Package;

final class CreatePackage
{
    private string $id;
    private string $url;
    private string $organizationId;

    public function __construct(string $id, string $organizationId, string $url)
    {
        $this->id = $id;
        $this->organizationId = $organizationId;
        $this->url = $url;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function url(): string
    {
        return $this->url;
    }
}
