<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Service\GitHubApi;
use Github\Exception\ExceptionInterface as GitHubApiExceptionInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class GitHubController extends OAuthController
{
    /**
     * @Route("/register/github", name="register_github_start", methods={"GET"})
     */
    public function register(): Response
    {
        return $this->oauth->getClient('github')->redirect(['user:email'], []);
    }

    /**
     * @Route("/auth/github", name="auth_github_start", methods={"GET"})
     */
    public function auth(): Response
    {
        return $this->oauth
            ->getClient('github')
            ->redirect(['user:email'], ['redirect_uri' => $this->generateUrl('login_github_check', [], UrlGeneratorInterface::ABSOLUTE_URL)])
            ;
    }

    /**
     * @Route("/register/github/check", name="register_github_check", methods={"GET"})
     */
    public function registerCheck(Request $request, GitHubApi $api): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('index');
        }

        try {
            $email = $api->primaryEmail($this->oauth->getClient('github')->getAccessToken()->getToken());

            return $this->createAndAuthenticateUser($email, $request);
        } catch (IdentityProviderException | GitHubApiExceptionInterface $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('app_register');
        }
    }
}
