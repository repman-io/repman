<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Buddy\Repman\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

final class UserGuardHelper
{
    private UserRepository $users;
    private GuardAuthenticatorHandler $guardHandler;
    private LoginFormAuthenticator $authenticator;

    public function __construct(UserRepository $users, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $authenticator)
    {
        $this->users = $users;
        $this->guardHandler = $guardHandler;
        $this->authenticator = $authenticator;
    }

    public function userExists(string $email): bool
    {
        return $this->users->emailExist($email);
    }

    public function authenticateUser(string $email, Request $request): void
    {
        $this->guardHandler->authenticateWithToken(
            $this->authenticator->createAuthenticatedToken($this->users->getByEmail($email), 'main'),
            $request
        );
    }
}
