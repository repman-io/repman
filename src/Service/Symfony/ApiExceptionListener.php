<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Buddy\Repman\Query\Api\Model\Error;
use Buddy\Repman\Query\Api\Model\Errors;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

final class ApiExceptionListener implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        if ($event->getRequest()->get('_route') === null || strpos($event->getRequest()->get('_route'), 'api_') !== 0) {
            return;
        }

        if ($event->getThrowable() instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse(null, Response::HTTP_FORBIDDEN));
        } elseif ($event->getThrowable() instanceof NotFoundHttpException) {
            $event->setResponse(new JsonResponse(null, Response::HTTP_NOT_FOUND));
        } elseif ($event->getThrowable() instanceof BadRequestHttpException) {
            $event->setResponse(new JsonResponse(null, Response::HTTP_BAD_REQUEST));
        } elseif ($event->getThrowable() instanceof AuthenticationCredentialsNotFoundException) {
            return;
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
