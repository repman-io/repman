<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Proxy\DownloadsQuery;

use Buddy\Repman\Query\Admin\Proxy\DownloadsQuery;
use Buddy\Repman\Query\Admin\Proxy\Model\Package;
use Doctrine\DBAL\Connection;

final class DbalDownloadsQuery implements DownloadsQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string[] $names
     *
     * @return Package[]
     */
    public function findByNames(array $names): array
    {
        return array_map(function (array $row): Package {
            return new Package(
                $row['package'],
                $row['downloads'],
                new \DateTimeImmutable($row['date'])
            );
        }, $this->connection->fetchAll('SELECT package, COUNT(package) AS downloads, MAX(date) AS date FROM proxy_package_download WHERE package IN (:packages) GROUP BY package ORDER BY package', [
            ':packages' => $names,
        ], [
            ':packages' => Connection::PARAM_STR_ARRAY,
        ]));
    }
}
