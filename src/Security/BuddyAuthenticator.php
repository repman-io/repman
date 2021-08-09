<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Buddy\Repman\Service\Integration\BuddyApi;
use Buddy\Repman\Service\Integration\BuddyApi\BuddyApiException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class BuddyAuthenticator extends OAuthAuthenticator
{
    private BuddyApi $buddyApi;

    public function __construct(ClientRegistry $clientRegistry, BuddyApi $buddyApi, RouterInterface $router, UserProvider $userProvider)
    {
        $this->clientRegistry = $clientRegistry;
        $this->buddyApi = $buddyApi;
        $this->userProvider = $userProvider;
        $this->router = $router;
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'login_buddy_check';
    }

    public function authenticate(Request $request): PassportInterface
    {
        try {
            $email = $this->buddyApi->primaryEmail($this->fetchAccessToken(
                $this->clientRegistry->getClient('buddy'),
                $request->attributes->get('_route')
            )->getToken());
        } catch (BuddyApiException $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage());
        }

        $user = $this->userProvider->loadUserByIdentifier($email);

        return new SelfValidatingPassport(new UserBadge($email, function () use ($user): UserInterface {
            return $user;
        }));
    }
}
