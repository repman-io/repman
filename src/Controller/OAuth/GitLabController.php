<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OauthToken;
use Buddy\Repman\Message\User\AddOauthToken;
use Buddy\Repman\Query\User\Model\Organization;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Munus\Collection\Set;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    /**
     * @Route("/organization/{organization}/package/add-from-gitlab", name="organization_package_add_from_gitlab", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageAddFromGithub(Organization $organization): Response
    {
        /** @var User */
        $user = $this->getUser();
        if ($user->oauthToken(OauthToken::TYPE_GITLAB)) {
            return $this->redirectToRoute('organization_package_new_from_gitlab', ['organization' => $organization->alias()]);
        }
        $this->session->set('organization', $organization->alias());

        return $this->oauth->getClient('gitlab-package')->redirect(['read_user', 'api'], []);
    }

    /**
     * @Route("/user/token/gitlab/check", name="package_gitlab_check", methods={"GET"})
     */
    public function storeRepoToken(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->dispatchMessage(
            new AddOauthToken(
                Uuid::uuid4()->toString(),
                $user->id()->toString(),
                OauthToken::TYPE_GITLAB,
                $this->oauth->getClient('gitlab-package')->getAccessToken()->getToken()
            )
        );

        return $this->redirectToRoute('organization_package_new_from_gitlab', [
            'organization' => $this->session->get('organization', Set::ofAll($user->getOrganizations()->toArray())->getOrElseThrow(new NotFoundHttpException())->alias()),
        ]);
    }
}
