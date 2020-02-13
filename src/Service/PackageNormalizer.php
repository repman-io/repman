<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Composer\Package\Dumper\ArrayDumper;
use Composer\Package\PackageInterface;

final class PackageNormalizer
{
    private ArrayDumper $dumper;

    public function __construct()
    {
        $this->dumper = new ArrayDumper();
    }

    /**
     * @return mixed[]
     */
    public function normalize(PackageInterface $package): array
    {
        $data = $this->dumper->dump($package);
        $data['uid'] = hash('crc32b', sprintf('%s:%s', $package->getPrettyName(), $package->getPrettyVersion()));

        return $data;
    }
}
