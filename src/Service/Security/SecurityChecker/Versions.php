<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\SecurityChecker;

use Composer\Semver\Semver;

final class Versions
{
    public function __construct(private readonly string $from, private readonly ?string $to = null)
    {
    }

    public function include(string $version): bool
    {
        $isLarger = Semver::satisfies($version, $this->from);
        $isSmaller = $this->to === null ? true : Semver::satisfies($version, $this->to);

        return $isLarger && $isSmaller;
    }
}
