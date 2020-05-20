<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\SecurityChecker;

use Composer\Semver\Semver;

final class Versions
{
    private string $from;
    private ?string $to;

    public function __construct(string $from, ?string $to = null)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function include(string $version): bool
    {
        $isLarger = Semver::satisfies($version, $this->from);
        $isSmaller = $this->to === null ? true : Semver::satisfies($version, $this->to);

        return $isLarger && $isSmaller;
    }
}
