<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\BitbucketApi;

final class Repository
{
    private string $name;
    private string $url;

    public function __construct(string $name, string $url)
    {
        $this->name = $name;
        $this->url = $url;
    }
}
