<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Buddy\Repman\Security\Model\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isDisabled()) {
            throw new CustomUserMessageAuthenticationException('Account is disabled.');
        }
    }
}
