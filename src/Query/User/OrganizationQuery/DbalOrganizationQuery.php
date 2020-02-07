<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\OrganizationQuery;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Token;
use Buddy\Repman\Query\User\OrganizationQuery;
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
    public function getByAlias(string $alias): Option
    {
        $data = $this->connection->fetchAssoc(
            'SELECT id, name, alias, owner_id FROM "organization" WHERE alias = :alias',
            [
                ':alias' => $alias,
            ]
        );

        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydrateOrganization($data));
    }

    /**
     * @return Token[]
     */
    public function findAllTokens(string $organizationId): array
    {
        return array_map(function (array $data): Token {
            return new Token(
                $data['name'],
                $data['value'],
                new \DateTimeImmutable($data['created_at']),
                $data['last_used_at'] !== null ? new \DateTimeImmutable($data['last_used_at']) : null
            );
        }, $this->connection->fetchAll('SELECT name, value, created_at, last_used_at FROM organization_token WHERE organization_id = :id', [
            ':id' => $organizationId,
        ]));
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
            $data['owner_id']
        );
    }
}
