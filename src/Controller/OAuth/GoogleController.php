<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class GoogleController extends OAuthController
{
    /**
     * @Route("/register/google", name="register_google_start", methods={"GET"})
     */
    public function register(): Response
    {
        $this->ensureOAuthRegistrationIsEnabled();

        return $this->oauth->getClient('google')->redirect([
            'openid',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ], []);
    }

    /**
     * @Route("/auth/google", name="auth_google_start", methods={"GET"})
     */
    public function auth(): Response
    {
        return $this->oauth
            ->getClient('google')
            ->redirect([
                'openid',
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile',
            ], ['redirect_uri' => $this->generateUrl('login_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL)])
        ;
    }

    /**
     * @Route("/register/google/check", name="register_google_check", methods={"GET"})
     */
    public function registerCheck(Request $request, GoogleClient $api): Response
    {
        $this->ensureOAuthRegistrationIsEnabled();

        return $this->createAndAuthenticateUser(
            'google',
            fn () => $api->fetchUserFromToken($this->oauth->getClient('google')->getAccessToken()->getToken()),
            $request
        );
    }
}
