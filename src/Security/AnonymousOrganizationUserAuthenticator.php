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
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
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
    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Authentication Required',
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function authenticate(Request $request): Passport
    {
        $organizationAlias = $request->attributes->get('organization');
        if ($organizationAlias === null) {
            throw new BadCredentialsException();
        }

        return new SelfValidatingPassport(new UserBadge($organizationAlias, function (string $organizationAlias): Organization {
            return $this->organizationProvider->loadUserByAlias($organizationAlias);
        }));
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') !== 'repo_package_downloads'
            && !$request->headers->has('PHP_AUTH_USER')
            && !$request->headers->has('PHP_AUTH_PW');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // never cacheable: a shared cache would serve this refusal to correctly authenticated clients too
        $response = (new JsonResponse([
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ], Response::HTTP_FORBIDDEN))->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }
}
