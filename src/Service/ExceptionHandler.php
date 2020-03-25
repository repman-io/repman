<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

interface ExceptionHandler
{
    public function handle(\Throwable $throwable): void;
}
