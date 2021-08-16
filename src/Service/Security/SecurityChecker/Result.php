<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\SecurityChecker;

final class Result
{
    private string $version;
    /**
     * @var Advisory[]
     */
    private array $advisories;

    /**
     * @param Advisory[] $advisories
     */
    public function __construct(string $version, array $advisories)
    {
        $this->version = $version;
        $this->advisories = $advisories;
    }

    /**
     * @return array<string,string|array<array<string,string>>>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'advisories' => array_map(fn ($advisory) => $advisory->toArray(), $this->advisories),
        ];
    }
}
