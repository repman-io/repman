<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\AddOauthToken;
use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Security\UserGuardHelper;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Munus\Collection\Set;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    protected function storeRepoToken(string $type, string $token, string $route): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->dispatchMessage(
            new AddOauthToken(
                Uuid::uuid4()->toString(),
                $user->id()->toString(),
                $type,
                $token
            )
        );

        return $this->redirectToRoute($route, [
            'organization' => $this->session->get('organization', Set::ofAll($user->getOrganizations()->toArray())->getOrElseThrow(new NotFoundHttpException())->alias()),
        ]);
    }
}
