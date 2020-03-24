<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Symfony;

use Buddy\Repman\Entity\User\OAuthToken\ExpiredOAuthTokenException;
use Buddy\Repman\Message\User\RefreshOAuthToken;
use Buddy\Repman\Service\Symfony\RefreshOAuthTokenMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class RefreshOAuthTokenMiddlewareTest extends TestCase
{
    public function testDispatchRefreshTokenMessageWhenExpiredTokenExceptionOccurs(): void
    {
        $envelope = new Envelope(new \stdClass());
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())->method('dispatch')->with(new RefreshOAuthToken(
            $userId = 'ea4f30b6-c706-4c7b-8c87-011ab50f8dee',
            $type = 'bitbucket'
        ))->willReturn(new Envelope(new \stdClass()));

        $stack = $this->createMock(StackInterface::class);
        $stack->expects($this->exactly(2))->method('next')->willThrowException(new HandlerFailedException(
            $envelope,
            [new ExpiredOAuthTokenException($userId, $type)]
        ));

        $middleware = new RefreshOAuthTokenMiddleware($messageBus);

        $this->expectException(HandlerFailedException::class);
        $middleware->handle($envelope, $stack);
    }

    public function testIgnoresAllOtherExceptions(): void
    {
        $envelope = new Envelope(new \stdClass());
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('dispatch');

        $stack = $this->createMock(StackInterface::class);
        $stack->expects($this->once())->method('next')->willThrowException(new HandlerFailedException(
            $envelope,
            [new \RuntimeException()]
        ));

        $middleware = new RefreshOAuthTokenMiddleware($messageBus);

        $this->expectException(HandlerFailedException::class);
        $middleware->handle($envelope, $stack);
    }
}
