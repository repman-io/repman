<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\OrganizationQuery;

use Buddy\Repman\Query\Api\Model\Organization;
use Buddy\Repman\Query\Api\Model\Token;
use Buddy\Repman\Query\Api\OrganizationQuery;
use Doctrine\DBAL\Connection;
use Munus\Control\Option;

final class DbalOrganizationQuery implements OrganizationQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Option<Organization>
     */
    public function getById(string $id): Option
    {
        $data = $this->connection->fetchAssociative(
            'SELECT id, name, alias, has_anonymous_access
            FROM "organization" WHERE id = :id', [
            'id' => $id,
        ]);

        return $data === false ? Option::none() : Option::some($this->hydrateOrganization($data));
    }

    /**
     * @return Organization[]
     */
    public function getUserOrganizations(string $userId, int $limit = 20, int $offset = 0): array
    {
        return array_map(function (array $data): Organization {
            return $this->hydrateOrganization($data);
        }, $this->connection->fetchAllAssociative(
            'SELECT o.id, o.name, o.alias, om.role, o.has_anonymous_access
            FROM organization_member om
            JOIN organization o ON o.id = om.organization_id
            WHERE om.user_id = :userId
            ORDER BY UPPER(o.name) ASC
            LIMIT :limit OFFSET :offset', [
            'userId' => $userId,
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    public function userOrganizationsCount(string $userId): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(o.*)
                FROM organization_member om
                JOIN organization o ON o.id = om.organization_id
                WHERE om.user_id = :userId',
                ['userId' => $userId]
            );
    }

    /**
     * @return Token[]
     */
    public function findAllTokens(string $organizationId, int $limit = 20, int $offset = 0): array
    {
        return array_map(function (array $data): Token {
            return $this->hydrateToken($data);
        }, $this->connection->fetchAllAssociative('
            SELECT name, value, created_at, last_used_at
            FROM organization_token
            WHERE organization_id = :id
            ORDER BY UPPER(name) ASC
            LIMIT :limit OFFSET :offset', [
            'id' => $organizationId,
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    public function tokenCount(string $organizationId): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(value) FROM organization_token WHERE organization_id = :id',
                ['id' => $organizationId]
            );
    }

    /**
     * @return Option<Token>
     */
    public function findToken(string $organizationId, string $value): Option
    {
        $data = $this->connection->fetchAssociative(
            'SELECT name, value, created_at, last_used_at
            FROM organization_token
            WHERE organization_id = :organization_id AND value = :value
            LIMIT 1', [
            'organization_id' => $organizationId,
            'value' => $value,
        ]);

        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydrateToken($data));
    }

    /**
     * @return Option<Token>
     */
    public function findTokenByName(string $organizationId, string $name): Option
    {
        $data = $this->connection->fetchAssociative(
            'SELECT name, value, created_at, last_used_at
            FROM organization_token
            WHERE organization_id = :organization_id AND name = :name
            ORDER BY created_at DESC', [
            'organization_id' => $organizationId,
            'name' => $name,
        ]);

        return $data === false ? Option::none() : Option::some($this->hydrateToken($data));
    }

    /**
     * @param array<mixed> $data
     */
    private function hydrateOrganization(array $data): Organization
    {
        return new Organization(
            $data['id'],
            $data['name'],
            $data['alias'],
            $data['has_anonymous_access'],
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    private function hydrateToken(array $data): Token
    {
        return new Token(
            $data['name'],
            $data['value'],
            new \DateTimeImmutable($data['created_at']),
            $data['last_used_at'] !== null ? new \DateTimeImmutable($data['last_used_at']) : null
        );
    }
}
