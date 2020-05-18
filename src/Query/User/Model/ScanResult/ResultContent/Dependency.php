<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\ScanResult\ResultContent;

final class Dependency
{
    private string $name;
    private string $version;

    /**
     * @var Advisory[]
     */
    private array $advisories;

    /**
     * @param Advisory[] $advisories
     */
    public function __construct(string $name, string $version, array $advisories)
    {
        $this->name = $name;
        $this->version = $version;
        $this->advisories = $advisories;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    /**
     * @return Advisory[]
     */
    public function advisories(): array
    {
        return $this->advisories;
    }
}
