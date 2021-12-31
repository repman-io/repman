<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Exception\InvalidStateException;
use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
use KnpU\OAuth2ClientBundle\Security\Exception\IdentityProviderAuthenticationException;
use KnpU\OAuth2ClientBundle\Security\Exception\InvalidStateAuthenticationException;
use KnpU\OAuth2ClientBundle\Security\Exception\NoAuthCodeAuthenticationException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

abstract class OAuthAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    protected ClientRegistry $clientRegistry;
    protected UserProvider $userProvider;
    protected RouterInterface $router;

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        if (($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) !== null) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('index'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        /** @var Session $session */
        $session = $request->getSession();

        $session->getFlashBag()->add('danger', strtr($exception->getMessageKey(), $exception->getMessageData()));

        return new RedirectResponse($this->router->generate('app_login'));
    }

    /**
     * @codeCoverageIgnore
     */
    protected function fetchAccessToken(OAuth2ClientInterface $client, string $route): AccessToken
    {
        try {
            return $client->getAccessToken([
                'redirect_uri' => $this->router->generate($route, [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
        } catch (MissingAuthorizationCodeException $e) {
            throw new NoAuthCodeAuthenticationException();
        } catch (IdentityProviderException $e) {
            throw new IdentityProviderAuthenticationException($e);
        } catch (InvalidStateException $e) {
            throw new InvalidStateAuthenticationException($e);
        }
    }
}
