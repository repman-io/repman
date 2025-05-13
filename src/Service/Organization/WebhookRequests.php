<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final class WebhookRequests
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function add(string $packageId, DateTimeImmutable $date, ?string $ip, ?string $userAgent): void
    {
        $this->connection->insert('organization_package_webhook_request', [
            'package_id' => $packageId,
            'date' => $date->format('Y-m-d H:i:s'),
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
