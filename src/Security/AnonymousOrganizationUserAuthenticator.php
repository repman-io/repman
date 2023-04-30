<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Buddy\Repman\Security\Model\Organization;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class AnonymousOrganizationUserAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private OrganizationProvider $organizationProvider;

    public function __construct(OrganizationProvider $organizationProvider)
    {
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * @codeCoverageIgnore
     */
    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Authentication Required',
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function authenticate(Request $request): PassportInterface
    {
        $organizationAlias = $request->get('organization');
        if ($organizationAlias === null) {
            throw new BadCredentialsException();
        }

        return new SelfValidatingPassport(new UserBadge($organizationAlias, function (string $organizationAlias): Organization {
            return $this->organizationProvider->loadUserByAlias($organizationAlias);
        }));
    }

    public function supports(Request $request): ?bool
    {
        return $request->get('_route') !== 'repo_package_downloads'
            && !$request->headers->has('PHP_AUTH_USER')
            && !$request->headers->has('PHP_AUTH_PW');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return (new JsonResponse([
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ], Response::HTTP_FORBIDDEN))->setMaxAge(60)->setPublic();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }
}
