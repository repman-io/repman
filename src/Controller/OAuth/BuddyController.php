<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\OAuth2\Client\Provider\Buddy;
use Buddy\Repman\Service\BuddyApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BuddyController extends OAuthController
{
    /**
     * @Route("/register/buddy", name="register_buddy_start", methods={"GET"})
     */
    public function register(): Response
    {
        return $this->oauth->getClient('buddy')->redirect([Buddy::SCOPE_USER_EMAIL], []);
    }

    /**
     * @Route("/auth/buddy", name="auth_buddy_start", methods={"GET"})
     */
    public function auth(): Response
    {
        return $this->oauth
            ->getClient('buddy')
            ->redirect([Buddy::SCOPE_USER_EMAIL], ['redirect_uri' => $this->generateUrl('login_buddy_check', [], UrlGeneratorInterface::ABSOLUTE_URL)])
            ;
    }

    /**
     * @Route("/register/buddy/check", name="register_buddy_check", methods={"GET"})
     */
    public function registerCheck(Request $request, BuddyApi $api): Response
    {
        return $this->createAndAuthenticateUser(
            'buddy',
            fn () => $api->primaryEmail($this->oauth->getClient('buddy')->getAccessToken()->getToken()),
            $request
        );
    }
}
