<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Ramsey\Uuid\Uuid;

final class Telemetry
{
    private string $instanceIdFile;

    public function __construct(string $instanceIdFile)
    {
        $this->instanceIdFile = $instanceIdFile;
    }

    public function generateInstanceId(): void
    {
        if (!$this->isInstanceIdPresent()) {
            \file_put_contents($this->instanceIdFile, Uuid::uuid4());
        }
    }

    public function isInstanceIdPresent(): bool
    {
        return \file_exists($this->instanceIdFile);
    }
}
