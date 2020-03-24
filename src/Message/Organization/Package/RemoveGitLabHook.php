<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Package;

final class RemoveGitLabHook
{
    private string $packageId;

    public function __construct(string $packageId)
    {
        $this->packageId = $packageId;
    }

    public function packageId(): string
    {
        return $this->packageId;
    }
}
