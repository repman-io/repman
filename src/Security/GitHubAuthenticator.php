<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Buddy\Repman\Service\GitHubApi;
use Github\Exception\ExceptionInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class GitHubAuthenticator extends SocialAuthenticator
{
    private ClientRegistry $clientRegistry;
    private GitHubApi $gitHubApi;
    private RouterInterface $router;
    private Session $session;

    public function __construct(ClientRegistry $clientRegistry, GitHubApi $gitHubApi, RouterInterface $router, Session $session)
    {
        $this->clientRegistry = $clientRegistry;
        $this->gitHubApi = $gitHubApi;
        $this->router = $router;
        $this->session = $session;
    }

    public function supports(Request $request)
    {
        return $request->attributes->get('_route') === 'login_github_check';
    }

    public function getCredentials(Request $request)
    {
        return $this->fetchAccessToken($this->clientRegistry->getClient('github'));
    }

    public function getUser($credentials, UserProviderInterface $userProvider): UserInterface
    {
        try {
            $email = $this->gitHubApi->primaryEmail($credentials->getToken());
        } catch (ExceptionInterface $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage());
        }

        return $userProvider->loadUserByUsername($email);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->session->getFlashBag()->add('danger', strtr($exception->getMessageKey(), $exception->getMessageData()));

        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): Response
    {
        return new RedirectResponse($this->router->generate('index'));
    }

    /**
     * @codeCoverageIgnore auth is started in LoginFormAuthenticator, see security.yml -> entry_point
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
