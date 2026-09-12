<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\PackageSynchronizer;

use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Repository\VcsRepository;

final class PreferTagVcsRepository extends VcsRepository
{
    private ?VcsDriverInterface $wrappedDriver = null;

    public function getDriver()
    {
        if ($this->wrappedDriver !== null) {
            return $this->wrappedDriver;
        }

        $driver = parent::getDriver();
        if ($driver === null) {
            return null;
        }

        return $this->wrappedDriver = new IgnoreComposerJsonVersionVcsDriver($driver);
    }
}
