<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Proxy;

use DateTimeImmutable;
use Buddy\Repman\Message\Proxy\AddDownloads\Package;

final class AddDownloads
{
    /**
     * @var Package[]
     */
    private array $packages;
    private DateTimeImmutable $date;
    private ?string $ip;
    private ?string $userAgent;

    /**
     * @param Package[] $packages
     */
    public function __construct(array $packages, DateTimeImmutable $date, ?string $ip, ?string $userAgent)
    {
        $this->packages = $packages;
        $this->date = $date;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
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
