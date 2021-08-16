<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Buddy\Repman\Query\Api\Model\Error;
use Buddy\Repman\Query\Api\Model\Errors;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    private ApiUserProvider $provider;

    public function __construct(ApiUserProvider $provider)
    {
        $this->provider = $provider;
    }

    public function supports(Request $request): bool
    {
        return true;
    }

    public function authenticate(Request $request): PassportInterface
    {
        if (!$request->headers->has('X-API-TOKEN') || $request->headers->get('X-API-TOKEN') === '') {
            throw new CustomUserMessageAuthenticationException('Authentication required.');
        }

        try {
            $user = $this->provider->loadUserByIdentifier($request->headers->get('X-API-TOKEN', ''));

            return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), function () use ($user): UserInterface {
                return $user;
            }));
        } catch (UserNotFoundException $exception) {
            throw new BadCredentialsException();
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(
            new Errors([new Error('credentials', strtr($exception->getMessageKey(), $exception->getMessageData()))]),
            Response::HTTP_UNAUTHORIZED
        );
    }
}
