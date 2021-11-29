<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Proxy\DownloadsQuery;

use Buddy\Repman\Query\Admin\Proxy\DownloadsQuery;
use Buddy\Repman\Query\Admin\Proxy\Model\Package;
use Buddy\Repman\Query\User\Model\Installs;
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
        $packages = [];
        foreach ($this->connection->fetchAllAssociative('SELECT package, COUNT(package) AS downloads, MAX(date) AS date FROM proxy_package_download WHERE package IN (:packages) GROUP BY package ORDER BY package', [
            'packages' => $names,
        ], [
            'packages' => Connection::PARAM_STR_ARRAY,
        ]) as $row) {
            $packages[$row['package']] = new Package(
                $row['downloads'],
                new \DateTimeImmutable($row['date'])
            );
        }

        return $packages;
    }

    public function getInstalls(int $lastDays = 30): Installs
    {
        return new Installs(
            array_map(function (array $row): Installs\Day {
                return new Installs\Day(substr($row['date'], 0, 10), $row['count']);
            }, $this->connection->fetchAllAssociative('SELECT * FROM (SELECT COUNT(*), DATE_TRUNC(\'day\', date) AS date FROM proxy_package_download WHERE date > :date GROUP BY DATE_TRUNC(\'day\', date)) AS installs ORDER BY date ASC', [
                'date' => (new \DateTimeImmutable())->modify(sprintf('-%s days', $lastDays))->format('Y-m-d'),
            ])),
            $lastDays,
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM proxy_package_download')
        );
    }
}
