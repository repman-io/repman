<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Security;

final class SendScanResult
{
    private string $organizationAlias;
    private string $packageName;
    private string $packageId;

    /**
     * @var mixed[]
     */
    private array $result;

    /**
     * @param mixed[] $result
     */
    public function __construct(string $organizationAlias, string $packageName, string $packageId, array $result)
    {
        $this->organizationAlias = $organizationAlias;
        $this->result = $result;
        $this->packageName = $packageName;
        $this->packageId = $packageId;
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
