<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\SecurityChecker;

final class Result
{
    /**
     * @param Advisory[] $advisories
     */
    public function __construct(private readonly string $version, private readonly array $advisories)
    {
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
