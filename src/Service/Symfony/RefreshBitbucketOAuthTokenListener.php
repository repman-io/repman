<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Symfony;

use Bitbucket\Exception\ClientErrorException;
use Buddy\Repman\Query\User\Model\Organization;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RefreshBitbucketOAuthTokenListener implements EventSubscriberInterface
{
    private UrlGeneratorInterface $router;
    private SessionInterface $session;

    public function __construct(UrlGeneratorInterface $router, SessionInterface $session)
    {
        $this->router = $router;
        $this->session = $session;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ClientErrorException || $exception->getCode() !== 401) {
            return;
        }

        $organization = $event->getRequest()->get('organization');
        if ($organization instanceof Organization) {
            $this->session->set('organization', $organization->alias());
        }

        $event->setResponse(new RedirectResponse($this->router->generate('refresh_bitbucket_token')));
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }
}
