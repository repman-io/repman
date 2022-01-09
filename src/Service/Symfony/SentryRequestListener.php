<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Buddy\Repman\Entity\User;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @codeCoverageIgnore
 */
final class SentryRequestListener implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (null === SentrySdk::getCurrentHub()->getClient()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $userData = [];

        if (null !== $token && $token->getUser() instanceof User) {
            /** @var User $user */
            $user = $token->getUser();
            $userData['id'] = $user->id();
        }

        $userData['ip_address'] = $event->getRequest()->getClientIp();

        SentrySdk::getCurrentHub()
            ->configureScope(function (Scope $scope) use ($userData): void {
                $scope->setUser($userData, true);
            });
    }

    /**
     * @return array<string,array<int,string|int>>
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 1]];
    }
}
