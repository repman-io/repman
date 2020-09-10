<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use Buddy\Repman\Entity\Organization\Package\ScanResult as ScanResultEntity;

final class ScanResult
{
    private \DateTimeImmutable $date;
    private string $status = ScanResultEntity::STATUS_PENDING;
    private string $version;
    private string $content;

    public static function statusPending(): string
    {
        return ScanResultEntity::STATUS_PENDING;
    }

    public function __construct(\DateTimeImmutable $date, string $status, string $version, string $content)
    {
        $this->date = $date;
        $this->status = $status;
        $this->version = $version;
        $this->content = $content;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function isOk(): bool
    {
        return $this->status() === ScanResultEntity::STATUS_OK;
    }

    public function isPending(): bool
    {
        return $this->status() === ScanResultEntity::STATUS_PENDING;
    }

    public function isNotAvailable(): bool
    {
        return $this->status() === ScanResultEntity::STATUS_NOT_AVAILABLE;
    }

    /**
     * @return mixed[]
     */
    public function content(): array
    {
        return json_decode($this->content, true);
    }
}
