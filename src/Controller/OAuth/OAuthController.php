<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\AddOAuthToken;
use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Message\User\RefreshOAuthToken;
use Buddy\Repman\Security\UserGuardHelper;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Exception\OAuth2ClientException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

abstract class OAuthController extends AbstractController
{
    protected UserGuardHelper $guard;
    protected ClientRegistry $oauth;
    protected SessionInterface $session;

    public function __construct(UserGuardHelper $guard, ClientRegistry $oauth, SessionInterface $session)
    {
        $this->guard = $guard;
        $this->oauth = $oauth;
        $this->session = $session;
    }

    /**
     * @Route("/user/token/{type}/refresh", name="refresh_oauth_token", methods={"GET"}, requirements={"type":"bitbucket|github|gitlab"})
     */
    public function refreshRepoToken(string $type): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->dispatchMessage(new RefreshOAuthToken($user->id()->toString(), $type));

        return $this->redirectToRoute('organization_package_new_from_bitbucket', [
            'organization' => $this->session->get('organization', $user->firstOrganizationAlias()->getOrElseThrow(new NotFoundHttpException())),
        ]);
    }

    protected function createAndAuthenticateUser(string $email, Request $request): Response
    {
        if (!$this->guard->userExists($email)) {
            $this->dispatchMessage(new CreateOAuthUser($email));
            $this->addFlash('success', 'Your account has been created. Please create a new organization.');
        } else {
            $this->addFlash('success', 'Your account already exists. You have been logged in automatically');
        }
        $this->guard->authenticateUser($email, $request);

        return $this->redirectToRoute('organization_create');
    }

    protected function storeRepoToken(string $type, OAuth2ClientInterface $client, string $route): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        try {
            $token = $client->getAccessToken();
            $this->dispatchMessage(
                new AddOAuthToken(
                    Uuid::uuid4()->toString(),
                    $user->id()->toString(),
                    $type,
                    $token->getToken(),
                    $token->getRefreshToken(),
                    $token->getExpires() !== null ? (new \DateTimeImmutable())->setTimestamp($token->getExpires()) : null
                )
            );

            return $this->redirectToRoute($route, [
                'organization' => $this->session->get('organization', $user->firstOrganizationAlias()->getOrElseThrow(new NotFoundHttpException())),
            ]);
        } catch (OAuth2ClientException | IdentityProviderException $e) {
            $this->addFlash('danger', 'Error while getting oauth token: '.$e->getMessage());

            return $this->redirectToRoute('organization_package_new', [
                'organization' => $this->session->get('organization', $user->firstOrganizationAlias()->getOrElseThrow(new NotFoundHttpException())),
            ]);
        }
    }
}
