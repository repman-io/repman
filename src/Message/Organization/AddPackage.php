<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class AddPackage
{
    private string $id;
    private string $url;
    private string $type;
    private string $organizationId;
    private ?string $oauthTokenId;

    public function __construct(string $id, string $organizationId, string $url, string $type = 'vcs', ?string $oauthTokenId = null)
    {
        $this->id = $id;
        $this->organizationId = $organizationId;
        $this->url = $url;
        $this->type = $type;
        $this->oauthTokenId = $oauthTokenId;
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

    public function type(): string
    {
        return $this->type;
    }

    public function oauthTokenId(): ?string
    {
        return $this->oauthTokenId;
    }
}
