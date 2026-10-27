<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Exception\IdentityProviderAuthenticationException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
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

    public function authenticate(Request $request): Passport
    {
        try {
            /** @var GitlabResourceOwner $gitLabUser */
            $gitLabUser = $this->clientRegistry->getClient('gitlab')->fetchUserFromToken($this->fetchAccessToken(
                $this->clientRegistry->getClient('gitlab'),
                $request->attributes->get('_route')
            ));
        } catch (IdentityProviderException|IdentityProviderAuthenticationException $exception) {
            throw new CustomUserMessageAuthenticationException($this->errorMessage($exception));
        }

        $user = $this->userProvider->loadUserByIdentifier($gitLabUser->getEmail());

        return new SelfValidatingPassport(new UserBadge($gitLabUser->getEmail(), function () use ($user): UserInterface {
            return $user;
        }));
    }

    private function errorMessage(\Throwable $exception): string
    {
        $providerException = $exception instanceof IdentityProviderException ? $exception : $exception->getPrevious();

        // GitLab answers errors such as rate limiting with an empty body, which the provider
        // reports as "Corrupted response"; the HTTP status says what actually went wrong.
        if ($providerException instanceof IdentityProviderException
            && $providerException->getMessage() === 'Corrupted response'
            && isset(Response::$statusTexts[$providerException->getCode()])
        ) {
            return Response::$statusTexts[$providerException->getCode()];
        }

        return $exception->getMessage();
    }
}
