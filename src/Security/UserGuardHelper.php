<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

final class UserGuardHelper
{
    private UserProvider $userProvider;
    private GuardAuthenticatorHandler $guardHandler;
    private LoginFormAuthenticator $authenticator;

    public function __construct(UserProvider $userProvider, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $authenticator)
    {
        $this->userProvider = $userProvider;
        $this->guardHandler = $guardHandler;
        $this->authenticator = $authenticator;
    }

    public function userExists(string $email): bool
    {
        return $this->userProvider->emailExist($email);
    }

    public function authenticateUser(string $email, Request $request): void
    {
        $this->guardHandler->authenticateWithToken(
            $this->authenticator->createAuthenticatedToken($this->userProvider->loadUserByUsername($email), 'main'),
            $request
        );
    }
}
