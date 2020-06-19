<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

final class AnonymousOrganizationUserAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @codeCoverageIgnore
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse([
            'message' => 'Authentication Required',
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function supports(Request $request)
    {
        return $request->get('_route') !== 'repo_package_downloads'
            && !$request->headers->has('PHP_AUTH_USER')
            && !$request->headers->has('PHP_AUTH_PW');
    }

    public function getCredentials(Request $request)
    {
        $organizationAlias = $request->get('organization');
        if ($organizationAlias === null) {
            throw new BadCredentialsException();
        }

        return $organizationAlias;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (!$userProvider instanceof OrganizationProvider) {
            throw new \InvalidArgumentException();
        }

        return $userProvider->loadUserByAlias($credentials);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse([
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ], Response::HTTP_FORBIDDEN);
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
