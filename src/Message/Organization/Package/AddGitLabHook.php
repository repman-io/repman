<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Package;

final class AddGitLabHook
{
    public function __construct(private readonly string $packageId)
    {
    }

    public function packageId(): string
    {
        return $this->packageId;
    }
}
