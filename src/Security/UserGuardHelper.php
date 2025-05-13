<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class UserGuardHelper
{
    public function __construct(private readonly UserProvider $userProvider, private readonly LoginFormAuthenticator $authenticator, private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function userExists(string $email): bool
    {
        return $this->userProvider->emailExist($email);
    }

    public function authenticateUser(string $email, Request $request): void
    {
        $token = $this->authenticator->createToken(new SelfValidatingPassport(new UserBadge($email, fn (string $email): UserInterface => $this->userProvider->loadUserByIdentifier($email))), 'main');
        $this->tokenStorage->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));
    }
}
