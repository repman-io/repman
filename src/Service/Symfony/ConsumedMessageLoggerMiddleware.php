<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

final class ConsumedMessageLoggerMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (!$envelope->last(ConsumedByWorkerStamp::class) instanceof StampInterface) {
            return $stack->next()->handle($envelope, $stack);
        }

        $memoryBefore = memory_get_usage(true);
        $start = microtime(true);

        $envelope = $stack->next()->handle($envelope, $stack);

        $consumeTime = microtime(true) - $start;
        $memoryAfter = memory_get_usage(true);

        $this->logger->info('Message consumed', [
            'messageClass' => $envelope->getMessage()::class,
            'memoryBefore' => $memoryBefore,
            'memoryAfter' => $memoryAfter,
            'memoryDelta' => $memoryAfter - $memoryBefore,
            'consumeTime' => $consumeTime,
        ]);

        return $envelope;
    }
}
