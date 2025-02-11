<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Buddy\Repman\Entity\User;
use Sentry\ClientInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @codeCoverageIgnore
 */
final class SentryRequestListener implements EventSubscriberInterface
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!SentrySdk::getCurrentHub()->getClient() instanceof ClientInterface) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $userData = [];

        if ($token instanceof TokenInterface && $token->getUser() instanceof User) {
            /** @var UserInterface $user */
            $user = $token->getUser();
            $userData['id'] = $user->id();
        }

        $userData['ip_address'] = $event->getRequest()->getClientIp();

        SentrySdk::getCurrentHub()
            ->configureScope(function (Scope $scope) use ($userData): void {
                $scope->setUser($userData);
            });
    }

    /**
     * @return array<string,array<int,string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 1]];
    }
}
