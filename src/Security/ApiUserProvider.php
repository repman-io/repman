<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Buddy\Repman\Security\Model\User;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class ApiUserProvider implements UserProviderInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $data = $this->getUserDataByApiToken($identifier);
        if ($data === false) {
            throw new UserNotFoundException();
        }

        $this->updateLastUsed($identifier);

        return $this->hydrateUser($data);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }

    /**
     * @return false|mixed[]
     */
    private function getUserDataByApiToken(string $apiToken)
    {
        return $this->connection->fetchAssociative(
            'SELECT
                u.id,
                u.email,
                u.password,
                u.status,
                u.email_confirmed_at,
                u.email_confirm_token,
                u.roles,
                u.email_scan_result,
                u.timezone
            FROM user_api_token a
            JOIN "user" u ON u.id = a.user_id
            WHERE a.value = :api_token
        ', ['api_token' => $apiToken]);
    }

    /**
     * @param mixed[] $data
     */
    private function hydrateUser(array $data): User
    {
        $organizations = $this->connection->fetchAllAssociative('
            SELECT o.name, o.alias, om.role, o.has_anonymous_access FROM organization_member om
            JOIN organization o ON o.id = om.organization_id
            WHERE om.user_id = :userId ORDER BY o.name
        ', ['userId' => $data['id']]);

        return new User(
            $data['id'],
            $data['email'],
            $data['password'],
            $data['status'],
            $data['email_confirmed_at'] !== null,
            $data['email_confirm_token'],
            json_decode($data['roles'], true),
            array_map(fn (array $data) => new User\Organization($data['alias'], $data['name'], $data['role'], $data['has_anonymous_access']), $organizations),
            $data['email_scan_result'],
            $data['timezone'],
        );
    }

    private function updateLastUsed(string $token): void
    {
        $this->connection->executeQuery(
            'UPDATE user_api_token
            SET last_used_at = :now WHERE value = :value', [
            'now' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'value' => $token,
        ]);
    }
}
