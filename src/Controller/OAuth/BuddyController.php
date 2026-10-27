<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Service\Integration\BuddyApi;
use Buddy\Repman\Service\OAuth\BuddyProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BuddyController extends OAuthController
{
    #[Route(path: '/register/buddy', name: 'register_buddy_start', methods: ['GET'])]
    public function register(): Response
    {
        $this->ensureOAuthRegistrationIsEnabled();

        return $this->oauth->getClient('buddy')->redirect([BuddyProvider::SCOPE_USER_EMAIL], []);
    }

    #[Route(path: '/auth/buddy', name: 'auth_buddy_start', methods: ['GET'])]
    public function auth(): Response
    {
        return $this->oauth
            ->getClient('buddy')
            ->redirect([BuddyProvider::SCOPE_USER_EMAIL], ['redirect_uri' => $this->generateUrl('login_buddy_check', [], UrlGeneratorInterface::ABSOLUTE_URL)])
        ;
    }

    #[Route(path: '/register/buddy/check', name: 'register_buddy_check', methods: ['GET'])]
    public function registerCheck(Request $request, BuddyApi $api): Response
    {
        $this->ensureOAuthRegistrationIsEnabled();

        return $this->createAndAuthenticateUser(
            'buddy',
            fn () => $api->primaryEmail($this->oauth->getClient('buddy')->getAccessToken()->getToken()),
            $request
        );
    }
}
