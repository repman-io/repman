<?php

declare(strict_types=1);

namespace Buddy\Repman\Security\Model;

use Buddy\Repman\Security\Model\User\Organization;
use Munus\Control\Option;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function count;

final class User implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param string[]       $roles
     * @param Organization[] $organizations
     */
    public function __construct(private readonly string $id, private readonly string $email, private readonly string $password, private readonly string $status, private readonly bool $emailConfirmed, private readonly string $emailConfirmToken, private readonly array $roles, private array $organizations, private readonly bool $emailScanResult, private readonly string $timezone)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function isDisabled(): bool
    {
        return $this->status === 'disabled';
    }

    public function isEmailConfirmed(): bool
    {
        return $this->emailConfirmed;
    }

    public function emailConfirmToken(): string
    {
        return $this->emailConfirmToken;
    }

    public function belongsToAnyOrganization(): bool
    {
        return $this->organizations !== [];
    }

    /**
     * @return Organization[]
     */
    public function organizations(): array
    {
        return $this->organizations;
    }

    public function getRoles(): array
    {
        // deny all access
        if ($this->isDisabled()) {
            return [];
        }

        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return Option<string>
     */
    public function firstOrganizationAlias(): Option
    {
        if ($this->organizations === []) {
            return Option::none();
        }

        return Option::some($this->organizations[0]->alias());
    }

    public function getUsername(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // e.x. $this->plainPassword = null;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->email !== $user->getUserIdentifier()) {
            return false;
        }

        if (count($user->getRoles()) !== count($this->getRoles()) || count($user->getRoles()) !== count(array_intersect($user->getRoles(), $this->getRoles()))) {
            return false;
        }

        return true;
    }

    public function emailScanResult(): bool
    {
        return $this->emailScanResult;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function isMemberOfOrganization(string $organizationAlias): bool
    {
        foreach ($this->organizations as $organization) {
            if ($organization->alias() === $organizationAlias) {
                return true;
            }
        }

        return false;
    }
}
