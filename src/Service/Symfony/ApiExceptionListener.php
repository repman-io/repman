<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionListener implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        if ($event->getRequest()->get('_route') === null) {
            return;
        }

        if (strpos($event->getRequest()->get('_route'), 'api_') !== 0) {
            return;
        }

        if (!$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        $event->setResponse(new JsonResponse(null, Response::HTTP_NOT_FOUND));
        $event->stopPropagation();
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
