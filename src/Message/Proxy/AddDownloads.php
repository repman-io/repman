<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Proxy;

use Buddy\Repman\Message\Proxy\AddDownloads\Package;
use DateTimeImmutable;

final class AddDownloads
{
    /**
     * @param Package[] $packages
     */
    public function __construct(private readonly array $packages, private readonly DateTimeImmutable $date, private readonly ?string $ip, private readonly ?string $userAgent)
    {
    }

    /**
     * @return Package[]
     */
    public function packages(): array
    {
        return $this->packages;
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
