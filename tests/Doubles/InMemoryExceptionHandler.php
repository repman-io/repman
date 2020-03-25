<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\ExceptionHandler;

final class InMemoryExceptionHandler implements ExceptionHandler
{
    /**
     * @var \Throwable[]
     */
    private array $exceptions = [];

    public function handle(\Throwable $throwable): void
    {
        $this->exceptions[] = $throwable;
    }

    public function exist(\Throwable $throwable): bool
    {
        return in_array($throwable, $this->exceptions, true);
    }
}
