<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\LoginFormAuthenticator;
use Buddy\Repman\Service\GitHubApi;
use Github\Exception\ExceptionInterface as GitHubApiExceptionInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GithubClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

final class GitHubController extends AbstractController
{
    private UserRepository $users;
    private GuardAuthenticatorHandler $guardHandler;
    private LoginFormAuthenticator $authenticator;

    public function __construct(UserRepository $users, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $authenticator)
    {
        $this->users = $users;
        $this->guardHandler = $guardHandler;
        $this->authenticator = $authenticator;
    }

    /**
     * @Route("/register/github", name="register_github_start")
     */
    public function register(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('github')
            ->redirect(['user:email'], [])
        ;
    }

    /**
     * @Route("/auth/github", name="auth_github_start")
     */
    public function auth(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('github')
            ->redirect(['user:email'], ['redirect_uri' => $this->generateUrl('login_github_check', [], UrlGeneratorInterface::ABSOLUTE_URL)])
            ;
    }

    /**
     * @Route("/register/github/check", name="register_github_check")
     */
    public function registerCheck(Request $request, ClientRegistry $clientRegistry, GitHubApi $api): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('index');
        }

        try {
            /** @var GithubClient $oauthClient */
            $oauthClient = $clientRegistry->getClient('github');
            $email = $api->primaryEmail($oauthClient->getAccessToken()->getToken());
            if ($this->users->findOneBy(['email' => $email]) === null) {
                $this->dispatchMessage(new CreateOAuthUser($email));
                $this->addFlash('success', 'Your account has been created. Please create a new organization.');
            } else {
                $this->addFlash('success', 'Your account already exists. You have been logged in automatically');
            }
            $this->guardHandler->authenticateWithToken($this->authenticator->createAuthenticatedToken($this->users->getByEmail($email), 'main'), $request);

            return $this->redirectToRoute('organization_create');
        } catch (IdentityProviderException | GitHubApiExceptionInterface $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('app_register');
        }
    }
}
