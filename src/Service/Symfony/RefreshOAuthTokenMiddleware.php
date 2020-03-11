<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Buddy\Repman\Entity\User\OAuthToken\ExpiredOAuthTokenException;
use Buddy\Repman\Message\User\RefreshOAuthToken;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class RefreshOAuthTokenMiddleware implements MiddlewareInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (HandlerFailedException $exception) {
            $firstException = current($exception->getNestedExceptions());
            if ($firstException instanceof ExpiredOAuthTokenException) {
                $this->messageBus->dispatch(new RefreshOAuthToken(
                    $firstException->userId(),
                    $firstException->type()
                ));
            }

            throw $exception;
        }
    }
}
