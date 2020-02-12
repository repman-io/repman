<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

final class TokenAuthenticator extends AbstractGuardAuthenticator
{
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            'message' => 'Authentication Required',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supports(Request $request)
    {
        return $request->headers->has('PHP_AUTH_USER') && $request->headers->has('PHP_AUTH_PW');
    }

    public function getCredentials(Request $request)
    {
        return [
            'token' => $request->headers->get('PHP_AUTH_PW'),
            'organization' => $request->get('organization'),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['token']);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $credentials['organization'] === $user->getUsername();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        return null;
    }

    /**
     * @codeCoverageIgnore
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }
}
