<?php

declare(strict_types=1);

namespace Buddy\Repman\Security\Model;

use Buddy\Repman\Security\Model\User\Organization;
use Munus\Control\Option;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class User implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface
{
    private string $id;
    private string $email;
    private string $password;
    private string $status;
    private bool $emailConfirmed;
    private string $emailConfirmToken;
    private bool $emailScanResult;
    private string $timezone;

    /**
     * @var string[]
     */
    private array $roles;

    /**
     * @var Organization[]
     */
    private array $organizations;

    /**
     * @param string[]       $roles
     * @param Organization[] $organizations
     */
    public function __construct(string $id, string $email, string $password, string $status, bool $emailConfirmed, string $emailConfirmToken, $roles, $organizations, bool $emailScanResult, string $timezone)
    {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->status = $status;
        $this->emailConfirmed = $emailConfirmed;
        $this->emailConfirmToken = $emailConfirmToken;
        $this->roles = $roles;
        $this->organizations = $organizations;
        $this->emailScanResult = $emailScanResult;
        $this->timezone = $timezone;
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
        return count($this->organizations) > 0;
    }

    /**
     * @return Organization[]
     */
    public function organizations(): array
    {
        return $this->organizations;
    }

    public function getRoles()
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
        if (count($this->organizations) === 0) {
            return Option::none();
        }

        return Option::some($this->organizations[0]->alias());
    }

    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
        return null;
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

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->getUserIdentifier() !== $user->getUserIdentifier()) {
            return false;
        }

        if (\count($user->getRoles()) !== \count($this->getRoles()) || \count($user->getRoles()) !== \count(array_intersect($user->getRoles(), $this->getRoles()))) {
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
}
