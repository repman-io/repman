<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

trait PackageScanResultTrait
{
    private ?ScanResult $scanResult;

    public function scanResultStatus(): string
    {
        return $this->scanResult !== null ? $this->scanResult->status() : ScanResult::statusPending();
    }

    public function scanResultDate(): ?\DateTimeImmutable
    {
        return $this->scanResult !== null ? $this->scanResult->date() : null;
    }

    public function isScanResultOk(): ?bool
    {
        return $this->scanResult !== null ? $this->scanResult->isOk() : false;
    }

    public function isScanResultPending(): bool
    {
        return $this->scanResult !== null ? $this->scanResult->isPending() : true;
    }

    public function isScanResultNotAvailable(): bool
    {
        return $this->scanResult !== null ? $this->scanResult->isNotAvailable() : true;
    }

    /**
     * @return mixed[]
     */
    public function lastScanResultContent(): array
    {
        return $this->scanResult !== null ? $this->scanResult->content() : [];
    }
}
