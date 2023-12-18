<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Exception\IdentityProviderAuthenticationException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class GitLabAuthenticator extends OAuthAuthenticator
{
    public function __construct(ClientRegistry $clientRegistry, RouterInterface $router, UserProvider $userProvider)
    {
        $this->clientRegistry = $clientRegistry;
        $this->userProvider = $userProvider;
        $this->router = $router;
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'login_gitlab_check';
    }

    public function authenticate(Request $request): PassportInterface
    {
        try {
            /** @var GitlabResourceOwner $gitLabUser */
            $gitLabUser = $this->clientRegistry->getClient('gitlab')->fetchUserFromToken($this->fetchAccessToken(
                $this->clientRegistry->getClient('gitlab'),
                $request->attributes->get('_route')
            ));
        } catch (IdentityProviderException|IdentityProviderAuthenticationException $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage());
        }

        $user = $this->userProvider->loadUserByIdentifier($gitLabUser->getEmail());

        return new SelfValidatingPassport(new UserBadge($gitLabUser->getEmail(), function () use ($user): UserInterface {
            return $user;
        }));
    }
}
