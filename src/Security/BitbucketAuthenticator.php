<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Bitbucket\Exception\ExceptionInterface;
use Buddy\Repman\Service\Integration\BitbucketApi;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class BitbucketAuthenticator extends OAuthAuthenticator
{
    public function __construct(ClientRegistry $clientRegistry, private readonly BitbucketApi $bitbucketApi, RouterInterface $router, UserProvider $userProvider)
    {
        $this->clientRegistry = $clientRegistry;
        $this->userProvider = $userProvider;
        $this->router = $router;
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'login_bitbucket_check';
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $email = $this->bitbucketApi->primaryEmail($this->fetchAccessToken(
                $this->clientRegistry->getClient('bitbucket'),
                $request->attributes->get('_route')
            )->getToken());
        } catch (ExceptionInterface $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage());
        }

        $user = $this->userProvider->loadUserByIdentifier($email);

        return new SelfValidatingPassport(new UserBadge($email, fn (): UserInterface => $user));
    }
}
