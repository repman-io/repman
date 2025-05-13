<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final class Downloads
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @throws Exception
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
