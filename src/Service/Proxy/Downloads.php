<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use DateTimeImmutable;
use Buddy\Repman\Service\Proxy\Downloads\Package;
use Doctrine\DBAL\Connection;

final class Downloads
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param Package[] $packages
     */
    public function save(array $packages, DateTimeImmutable $date, ?string $ip, ?string $userAgent): void
    {
        foreach ($packages as $package) {
            $this->connection->insert('proxy_package_download', [
                'package' => $package->name(),
                'version' => $package->version(),
                'date' => $date->format('Y-m-d H:i:s'),
                'ip' => $ip,
                'user_agent' => $userAgent,
            ]);
        }
    }
}
