<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Bitbucket\Exception\ExceptionInterface as BitbucketApiExceptionInterface;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Service\BitbucketApi;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BitbucketController extends OAuthController
{
    /**
     * @Route("/register/bitbucket", name="register_bitbucket_start", methods={"GET"})
     */
    public function register(): Response
    {
        return $this->oauth->getClient('bitbucket')->redirect(['email'], []);
    }

    /**
     * @Route("/auth/bitbucket", name="auth_bitbucket_start", methods={"GET"})
     */
    public function auth(ClientRegistry $clientRegistry): Response
    {
        return $this->oauth->getClient('bitbucket')->redirect(['email'], ['redirect_uri' => $this->generateUrl('login_bitbucket_check', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
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
            $email = $api->primaryEmail($this->oauth->getClient('bitbucket')->getAccessToken()->getToken());

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
        if ($user->oauthToken(OAuthToken::TYPE_BITBUCKET) !== null) {
            return $this->redirectToRoute('organization_package_new', ['organization' => $organization->alias(), 'type' => OAuthToken::TYPE_BITBUCKET]);
        }
        $this->session->set('organization', $organization->alias());

        return $this->oauth->getClient('bitbucket')->redirect(['repository', 'webhook'], ['redirect_uri' => $this->generateUrl('package_bitbucket_check', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
    }

    /**
     * @Route("/user/token/bitbucket/check", name="package_bitbucket_check", methods={"GET"})
     */
    public function storeBitbucketRepoToken(): Response
    {
        return $this->storeRepoToken(
            OAuthToken::TYPE_BITBUCKET,
            function (): AccessToken {
                return $this->oauth->getClient('bitbucket')->getAccessToken(['redirect_uri' => $this->generateUrl('package_bitbucket_check', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
            },
            'organization_package_new'
        );
    }
}
