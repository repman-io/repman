<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Buddy\Repman\Security\Model\Organization;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class OrganizationProvider implements UserProviderInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function loadUserByUsername(string $username)
    {
        $data = $this->getUserDataByToken($username);

        if ($data === false) {
            throw new BadCredentialsException();
        }

        $this->updateLastUsed($username);

        return $this->hydrateOrganization($data);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $data = $this->getUserDataByToken($identifier);

        if ($data === false) {
            throw new UserNotFoundException();
        }

        $this->updateLastUsed($identifier);

        return $this->hydrateOrganization($data);
    }

    public function refreshUser(UserInterface $user)
    {
        return $this->loadUserByIdentifier((string) $user->getPassword());
    }

    public function supportsClass(string $class)
    {
        return $class === Organization::class;
    }

    public function loadUserByAlias(string $alias): Organization
    {
        $data = $this->getUserDataByAlias($alias);
        if ($data === false) {
            throw new BadCredentialsException();
        }

        return $this->hydrateOrganization($data);
    }

    private function updateLastUsed(string $token): void
    {
        $this->connection->executeQuery('UPDATE organization_token SET last_used_at = :now WHERE value = :value', [
            'now' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'value' => $token,
        ]);
    }

    /**
     * @return false|mixed[]
     */
    private function getUserDataByToken(string $token)
    {
        return $this->connection->fetchAssociative('
            SELECT t.value, o.name, o.alias, o.id FROM organization_token t
            JOIN organization o ON o.id = t.organization_id
            WHERE t.value = :token',
            [
                'token' => $token,
            ]);
    }

    /**
     * @return false|mixed[]
     */
    private function getUserDataByAlias(string $alias)
    {
        return $this->connection->fetchAssociative("
            SELECT id, name, alias, 'anonymous' AS value
            FROM organization
            WHERE alias = :alias AND has_anonymous_access = true",
            [
                'alias' => $alias,
            ]);
    }

    /**
     * @param mixed[] $data
     */
    private function hydrateOrganization(array $data): Organization
    {
        return new Organization(
            $data['id'],
            $data['name'],
            $data['alias'],
            $data['value']
        );
    }
}
