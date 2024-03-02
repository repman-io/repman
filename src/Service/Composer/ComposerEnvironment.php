<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Composer;

final class ComposerEnvironment
{
    private string $version;

    public function __construct(
        string $version
    ) {
        $this->version = $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
