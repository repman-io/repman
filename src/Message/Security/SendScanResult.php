<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Security;

final class SendScanResult
{
    /**
     * @var string[]
     */
    private array $emails;
    private string $organizationAlias;
    private string $packageName;
    private string $packageId;

    /**
     * @var mixed[]
     */
    private array $result;

    /**
     * @param string[] $emails
     * @param mixed[]  $result
     */
    public function __construct(array $emails, string $organizationAlias, string $packageName, string $packageId, array $result)
    {
        $this->emails = $emails;
        $this->organizationAlias = $organizationAlias;
        $this->result = $result;
        $this->packageName = $packageName;
        $this->packageId = $packageId;
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
