<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

use DateTimeImmutable;

final class AddDownload
{
    public function __construct(private readonly string $packageId, private readonly string $version, private readonly DateTimeImmutable $date, private readonly ?string $ip, private readonly ?string $userAgent)
    {
    }

    public function packageId(): string
    {
        return $this->packageId;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function ip(): ?string
    {
        return $this->ip;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }
}
