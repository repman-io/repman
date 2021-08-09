<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\UserQuery;
use Buddy\Repman\Security\Model\User;
use League\OAuth2\Client\Token\AccessToken;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
        $this->ensureOAuthRegistrationIsEnabled();

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
        $this->ensureOAuthRegistrationIsEnabled();

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
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}/package/add-from-gitlab", name="fetch_gitlab_package_token", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageAddFromGitLab(Request $request, Organization $organization, UserQuery $userQuery): Response
    {
        /** @var User */
        $user = $this->getUser();
        if ($userQuery->hasOAuthAccessToken($user->id(), OAuthToken::TYPE_GITLAB)) {
            return $this->redirectToRoute('organization_package_new', ['organization' => $organization->alias(), 'type' => OAuthToken::TYPE_GITLAB]);
        }
        $request->getSession()->set('organization', $organization->alias());

        return $this->oauth->getClient('gitlab')->redirect(['read_user', 'api'], ['redirect_uri' => $this->generateUrl('package_gitlab_check', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
    }

    /**
     * @Route("/user/token/gitlab/check", name="package_gitlab_check", methods={"GET"})
     */
    public function storeGitLabRepoToken(Request $request): Response
    {
        return $this->storeRepoToken(
            $request,
            OAuthToken::TYPE_GITLAB,
            function (): AccessToken {
                return $this->oauth->getClient('gitlab')->getAccessToken(['redirect_uri' => $this->generateUrl('package_gitlab_check', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
            },
            'organization_package_new'
        );
    }
}
