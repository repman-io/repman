<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class GoogleAuthenticator extends OAuthAuthenticator
{
    private GoogleClient $googleClient;

    public function __construct(ClientRegistry $clientRegistry, GoogleClient $googleClient, RouterInterface $router, UserProvider $userProvider)
    {
        $this->clientRegistry = $clientRegistry;
        $this->googleClient = $googleClient;
        $this->userProvider = $userProvider;
        $this->router = $router;
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'login_google_check';
    }

    public function authenticate(Request $request): PassportInterface
    {
        $email = $this->googleClient->fetchUserFromToken($this->fetchAccessToken($this->clientRegistry->getClient('google'), $request->attributes->get('_route')))->getEmail();
        $user = $this->userProvider->loadUserByIdentifier($email);

        return new SelfValidatingPassport(new UserBadge($email, function () use ($user): UserInterface {
            return $user;
        }));
    }
}
