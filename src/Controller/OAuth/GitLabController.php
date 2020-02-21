<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\LoginFormAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

final class GitLabController extends AbstractController
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
     * @Route("/register/gitlab", name="register_gitlab_start")
     */
    public function register(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('gitlab-register')
            ->redirect(['read_user'], [])
        ;
    }

    /**
     * @Route("/auth/gitlab", name="auth_gitlab_start")
     */
    public function auth(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('gitlab-auth')
            ->redirect(['read_user'], [])
        ;
    }

    /**
     * @Route("/register/gitlab/check", name="register_gitlab_check")
     */
    public function registerCheck(Request $request, ClientRegistry $clientRegistry): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('index');
        }

        try {
            /** @var GitlabResourceOwner $user */
            $user = $clientRegistry->getClient('gitlab-register')->fetchUser();

            if ($this->users->findOneBy(['email' => $user->getEmail()]) === null) {
                $this->dispatchMessage(new CreateOAuthUser($user->getEmail()));
                $this->addFlash('success', 'Your account has been created. Please create a new organization.');
            } else {
                $this->addFlash('success', 'Your account already exists. You have been logged in automatically');
            }
            $this->guardHandler->authenticateWithToken($this->authenticator->createAuthenticatedToken($this->users->getByEmail($user->getEmail()), 'main'), $request);

            return $this->redirectToRoute('organization_create');
        } catch (IdentityProviderException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('app_register');
        }
    }
}
