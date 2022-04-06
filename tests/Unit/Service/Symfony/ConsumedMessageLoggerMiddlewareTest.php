<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Symfony;

use stdClass;
use Buddy\Repman\Service\Symfony\ConsumedMessageLoggerMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;

final class ConsumedMessageLoggerMiddlewareTest extends TestCase
{
    public function testIgnoreSyncMessages(): void
    {
        $envelope = new Envelope(new stdClass());
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->method('handle')->willReturn($envelope);
        $stack = $this->createMock(StackInterface::class);
        $stack->method('next')->willReturn($middleware);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('info');

        $middleware = new ConsumedMessageLoggerMiddleware($logger);
        $middleware->handle($envelope, $stack);
    }

    public function testCollectMetricsForMessage(): void
    {
        $envelope = new Envelope(new stdClass(), [new ConsumedByWorkerStamp()]);
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->method('handle')->willReturn($envelope);
        $stack = $this->createMock(StackInterface::class);
        $stack->method('next')->willReturn($middleware);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('info')->with('Message consumed', self::arrayHasKey('consumeTime'));

        $middleware = new ConsumedMessageLoggerMiddleware($logger);
        $middleware->handle($envelope, $stack);
    }
}
