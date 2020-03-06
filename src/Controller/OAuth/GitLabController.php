<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class GitLabController extends OAuthController
{
    /**
     * @Route("/register/gitlab", name="register_gitlab_start", methods={"GET"})
     */
    public function register(): Response
    {
        return $this->oauth->getClient('gitlab-register')->redirect(['read_user'], []);
    }

    /**
     * @Route("/auth/gitlab", name="auth_gitlab_start", methods={"GET"})
     */
    public function auth(): Response
    {
        return $this->oauth->getClient('gitlab-auth')->redirect(['read_user'], ['redirect_uri' => $this->generateUrl('login_gitlab_check', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
    }

    /**
     * @Route("/register/gitlab/check", name="register_gitlab_check", methods={"GET"})
     */
    public function registerCheck(Request $request): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('index');
        }

        try {
            /** @var GitlabResourceOwner $user */
            $user = $this->oauth->getClient('gitlab-register')->fetchUser();

            return $this->createAndAuthenticateUser($user->getEmail(), $request);
        } catch (IdentityProviderException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('app_register');
        }
    }
}
