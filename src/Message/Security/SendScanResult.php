<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Security;

final class SendScanResult
{
    /**
     * @param string[] $emails
     * @param mixed[]  $result
     */
    public function __construct(private readonly array $emails, private readonly string $organizationAlias, private readonly string $packageName, private readonly string $packageId, private readonly array $result)
    {
    }

    /**
     * @return string[]
     */
    public function emails(): array
    {
        return $this->emails;
    }

    public function organizationAlias(): string
    {
        return $this->organizationAlias;
    }

    public function packageName(): string
    {
        return $this->packageName;
    }

    public function packageId(): string
    {
        return $this->packageId;
    }

    /**
     * @return mixed[]
     */
    public function result(): array
    {
        return $this->result;
    }
}
