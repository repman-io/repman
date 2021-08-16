<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\UserQuery;
use Buddy\Repman\Security\Model\User;
use Buddy\Repman\Service\Integration\GitHubApi;
use League\OAuth2\Client\Token\AccessToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
        $this->ensureOAuthRegistrationIsEnabled();

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
        $this->ensureOAuthRegistrationIsEnabled();

        return $this->createAndAuthenticateUser(
            'github',
            fn () => $api->primaryEmail($this->oauth->getClient('github')->getAccessToken()->getToken()),
            $request
        );
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}/package/add-from-github", name="fetch_github_package_token", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageAddFromGithub(Request $request, Organization $organization, UserQuery $userQuery): Response
    {
        /** @var User */
        $user = $this->getUser();
        if ($userQuery->hasOAuthAccessToken($user->id(), OAuthToken::TYPE_GITHUB)) {
            return $this->redirectToRoute('organization_package_new', ['organization' => $organization->alias(), 'type' => OAuthToken::TYPE_GITHUB]);
        }
        $request->getSession()->set('organization', $organization->alias());

        return $this->oauth->getClient('github')
            ->redirect(
                ['read:org', 'repo'],
                ['redirect_uri' => $this->generateUrl('package_github_check', [], UrlGeneratorInterface::ABSOLUTE_URL)]
            );
    }

    /**
     * @Route("/user/token/github/check", name="package_github_check", methods={"GET"})
     */
    public function storeGitHubRepoToken(Request $request): Response
    {
        return $this->storeRepoToken(
            $request,
            OAuthToken::TYPE_GITHUB,
            function (): AccessToken {
                return $this->oauth->getClient('github')->getAccessToken();
            },
            'organization_package_new'
        );
    }
}
