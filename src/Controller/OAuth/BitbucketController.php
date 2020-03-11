<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Bitbucket\Exception\ExceptionInterface as BitbucketApiExceptionInterface;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Message\User\RefreshOAuthToken;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Service\BitbucketApi;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class BitbucketController extends OAuthController
{
    /**
     * @Route("/register/bitbucket", name="register_bitbucket_start", methods={"GET"})
     */
    public function register(): Response
    {
        return $this->oauth->getClient('bitbucket-register')->redirect(['email'], []);
    }

    /**
     * @Route("/auth/bitbucket", name="auth_bitbucket_start", methods={"GET"})
     */
    public function auth(ClientRegistry $clientRegistry): Response
    {
        return $this->oauth->getClient('bitbucket-auth')->redirect(['email'], []);
    }

    /**
     * @Route("/register/bitbucket/check", name="register_bitbucket_check", methods={"GET"})
     */
    public function registerCheck(Request $request, BitbucketApi $api): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('index');
        }

        try {
            $email = $api->primaryEmail($this->oauth->getClient('bitbucket-register')->getAccessToken()->getToken());

            return $this->createAndAuthenticateUser($email, $request);
        } catch (IdentityProviderException | BitbucketApiExceptionInterface $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('app_register');
        }
    }

    /**
     * @Route("/organization/{organization}/package/add-from-bitbucket", name="fetch_bitbucket_package_token", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageAddFromBitbucket(Organization $organization): Response
    {
        /** @var User */
        $user = $this->getUser();
        if ($user->oauthToken(OAuthToken::TYPE_BITBUCKET)) {
            return $this->redirectToRoute('organization_package_new_from_bitbucket', ['organization' => $organization->alias()]);
        }
        $this->session->set('organization', $organization->alias());

        return $this->oauth->getClient('bitbucket-package')->redirect(['repository', 'webhook'], []);
    }

    /**
     * @Route("/user/token/bitbucket/check", name="package_bitbucket_check", methods={"GET"})
     */
    public function storeGitLabRepoToken(): Response
    {
        return $this->storeRepoToken(
            OAuthToken::TYPE_BITBUCKET,
            $this->oauth->getClient('bitbucket-package'),
            'organization_package_new_from_bitbucket'
        );
    }

    /**
     * @Route("/user/token/bitbucket/refresh", name="refresh_bitbucket_token", methods={"GET"})
     */
    public function refreshRepoToken(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->dispatchMessage(new RefreshOAuthToken($user->id()->toString(), OAuthToken::TYPE_BITBUCKET));

        return $this->redirectToRoute('organization_package_new_from_bitbucket', [
            'organization' => $this->session->get('organization', $user->firstOrganizationAlias()->getOrElseThrow(new NotFoundHttpException())),
        ]);
    }
}
