<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Bitbucket\Exception\ExceptionInterface as BitbucketApiExceptionInterface;
use Buddy\Repman\Service\BitbucketApi;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
}
