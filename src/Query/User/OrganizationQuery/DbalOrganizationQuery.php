<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\OrganizationQuery;

use Buddy\Repman\Query\User\Model\Organization;
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
