<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Security\UserGuardHelper;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class OAuthController extends AbstractController
{
    protected UserGuardHelper $guard;
    protected ClientRegistry $oauth;

    public function __construct(UserGuardHelper $guard, ClientRegistry $oauth)
    {
        $this->guard = $guard;
        $this->oauth = $oauth;
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
}
