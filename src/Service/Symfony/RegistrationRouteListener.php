<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Buddy\Repman\Service\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class RegistrationRouteListener implements EventSubscriberInterface
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->config->userRegistrationEnabled()) {
            return;
        }

        $route = $event->getRequest()->get('_route');
        if ($route === 'app_register' || strpos((string) $route, 'register_') === 0) {
            throw new NotFoundHttpException('Registration is disabled');
        }
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array<string,array<int,string|int>>
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 1]];
    }
}
