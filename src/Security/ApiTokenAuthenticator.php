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
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiTokenAuthenticator extends AbstractGuardAuthenticator
{
    public function supports(Request $request)
    {
        return $request->headers->has('X-API-TOKEN');
    }

    public function getCredentials(Request $request)
    {
        return ['api_token' => $request->headers->get('X-API-TOKEN')];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            return $userProvider->loadUserByUsername($credentials['api_token']);
        } catch (UsernameNotFoundException $exception) {
            throw new BadCredentialsException();
        }
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->unAuthorized(strtr($exception->getMessageKey(), $exception->getMessageData()));
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return $this->unAuthorized('Authentication required.');
    }

    /**
     * @codeCoverageIgnore
     */
    public function supportsRememberMe()
    {
        return false;
    }

    private function unAuthorized(string $message): JsonResponse
    {
        return new JsonResponse(
            new Errors([new Error('credentials', $message)]),
            Response::HTTP_UNAUTHORIZED
        );
    }
}
