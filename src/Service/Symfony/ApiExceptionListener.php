<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Buddy\Repman\Query\Api\Model\Error;
use Buddy\Repman\Query\Api\Model\Errors;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

final class ApiExceptionListener implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        if (strpos($event->getRequest()->getPathInfo(), '/api') !== 0) {
            return;
        }

        if ($event->getThrowable() instanceof AccessDeniedException) {
            $event->setResponse((new JsonResponse(null, Response::HTTP_FORBIDDEN))->setMaxAge(60)->setPublic());
        } elseif ($event->getThrowable() instanceof AuthenticationCredentialsNotFoundException) {
            return;
        } elseif ($event->getThrowable() instanceof HttpException) {
            $event->setResponse(new JsonResponse(null, $event->getThrowable()->getStatusCode()));
        } else {
            $event->setResponse(new JsonResponse(
                new Errors([
                    new Error(
                        get_class($event->getThrowable()),
                        $event->getThrowable()->getMessage()
                    ),
                ]),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array<string,array<int,string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 1]];
    }
}
