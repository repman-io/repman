<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class ChangeSecurityScanConfiguration
{
    private string $organizationId;
    private bool $enableSecurityScan;

    public function __construct(string $organizationId, bool $enableSecurityScan)
    {
        $this->organizationId = $organizationId;
        $this->enableSecurityScan = $enableSecurityScan;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function hasSecurityScanEnabled(): bool
    {
        return $this->enableSecurityScan;
    }
}
