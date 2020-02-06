<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\PackageQuery;

use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\PackageQuery;
use Doctrine\DBAL\Connection;
use Munus\Control\Option;

final class DbalPackageQuery implements PackageQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Package[]
     */
    public function findAll(int $limit = 20, int $offset = 0): array
    {
        return array_map(function (array $data): Package {
            return $this->hydratePackage($data);
        }, $this->connection->fetchAll(
            'SELECT id, repository_url, name, latest_released_version, latest_release_date, description
            FROM "package" LIMIT :limit OFFSET :offset', [
            ':limit' => $limit,
            ':offset' => $offset,
        ]));
    }

    public function count(): int
    {
        return (int) $this
            ->connection
            ->fetchColumn('SELECT COUNT(id) FROM "package"');
    }

    /**
     * @return Option<Package>
     */
    public function getById(string $id): Option
    {
        $data = $this->connection->fetchAssoc(
            'SELECT id, repository_url, name, latest_released_version, latest_release_date, description
            FROM "package" WHERE id = :id', [
            ':id' => $id,
        ]);
        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydratePackage($data));
    }

    /**
     * @param array<mixed> $data
     */
    private function hydratePackage(array $data): Package
    {
        return new Package(
            $data['id'],
            $data['repository_url'],
            $data['name'],
            $data['latest_released_version'],
            new \DateTimeImmutable($data['latest_release_date']),
            $data['description'],
        );
    }
}
