<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Query\User\Model\Organization;
use League\OAuth2\Client\Token\AccessToken;
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
        return $this->oauth->getClient('gitlab')->redirect(['read_user'], []);
    }

    /**
     * @Route("/auth/gitlab", name="auth_gitlab_start", methods={"GET"})
     */
    public function auth(): Response
    {
        return $this->oauth->getClient('gitlab')->redirect(['read_user'], ['redirect_uri' => $this->generateUrl('login_gitlab_check', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
    }

    /**
     * @Route("/register/gitlab/check", name="register_gitlab_check", methods={"GET"})
     */
    public function registerCheck(Request $request): Response
    {
        return $this->createAndAuthenticateUser(
            'gitlab',
            function (): string {
                /** @var GitlabResourceOwner $user */
                $user = $this->oauth->getClient('gitlab')->fetchUser();

                return $user->getEmail();
            },
            $request
        );
    }

    /**
     * @Route("/organization/{organization}/package/add-from-gitlab", name="fetch_gitlab_package_token", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageAddFromGitLab(Organization $organization): Response
    {
        /** @var User */
        $user = $this->getUser();
        if ($user->oauthToken(OAuthToken::TYPE_GITLAB) !== null) {
            return $this->redirectToRoute('organization_package_new', ['organization' => $organization->alias(), 'type' => OAuthToken::TYPE_GITLAB]);
        }
        $this->session->set('organization', $organization->alias());

        return $this->oauth->getClient('gitlab')->redirect(['read_user', 'api'], ['redirect_uri' => $this->generateUrl('package_gitlab_check', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
    }

    /**
     * @Route("/user/token/gitlab/check", name="package_gitlab_check", methods={"GET"})
     */
    public function storeGitLabRepoToken(): Response
    {
        return $this->storeRepoToken(
            OAuthToken::TYPE_GITLAB,
            function (): AccessToken {
                return $this->oauth->getClient('gitlab')->getAccessToken(['redirect_uri' => $this->generateUrl('package_gitlab_check', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
            },
            'organization_package_new'
        );
    }
}
