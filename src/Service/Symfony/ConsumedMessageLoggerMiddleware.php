<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;

final class ConsumedMessageLoggerMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $consumerLogger)
    {
        $this->logger = $consumerLogger;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if ($envelope->last(ConsumedByWorkerStamp::class) === null) {
            return $stack->next()->handle($envelope, $stack);
        }

        $memoryBefore = memory_get_usage(true);
        $start = microtime(true);

        $envelope = $stack->next()->handle($envelope, $stack);

        $consumeTime = microtime(true) - $start;
        $memoryAfter = memory_get_usage(true);

        $this->logger->info('Message consumed', [
            'messageClass' => get_class($envelope->getMessage()),
            'memoryBefore' => $memoryBefore,
            'memoryAfter' => $memoryAfter,
            'memoryDelta' => $memoryAfter - $memoryBefore,
            'consumeTime' => $consumeTime,
        ]);

        return $envelope;
    }
}
