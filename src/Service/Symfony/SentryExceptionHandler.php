<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Buddy\Repman\Service\ExceptionHandler;
use function Sentry\captureException;

final class SentryExceptionHandler implements ExceptionHandler
{
    /**
     * @codeCoverageIgnore
     */
    public function handle(\Throwable $throwable): void
    {
        captureException($throwable);
    }
}
