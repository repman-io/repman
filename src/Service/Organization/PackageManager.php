<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Munus\Control\Option;

final class PackageManager
{
    private Storage $distStorage;
    private string $baseDir;

    public function __construct(Storage $distStorage, string $baseDir)
    {
        $this->distStorage = $distStorage;
        $this->baseDir = $baseDir;
    }

    /**
     * @param PackageName[] $packages
     *
     * @return mixed[]
     */
    public function findProviders(string $organizationAlias, array $packages): array
    {
        $data = [];
        foreach ($packages as $package) {
            $filepath = $this->filepath($organizationAlias, $package->name());
            if (!is_readable($filepath)) {
                continue;
            }

            $json = unserialize((string) file_get_contents($filepath));
            $data = array_merge($data, $json['packages'] ?? []);
        }

        return $data;
    }

    /**
     * @param mixed[] $json
     */
    public function saveProvider(array $json, string $organizationAlias, string $packageName): void
    {
        $filepath = $this->filepath($organizationAlias, $packageName);

        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($filepath, serialize($json));
    }

    public function removeProvider(string $organizationAlias, string $packageName): self
    {
        $file = $this->filepath($organizationAlias, $packageName);

        if (is_file($file)) {
            unlink($file);
            rmdir(dirname($file));
        }

        return $this;
    }

    public function removeOrganizationDir(string $organizationAlias): self
    {
        $base = $this->baseDir.'/'.$organizationAlias;

        if (is_dir($dir = $base.'/p/')) {
            rmdir($dir);
            rmdir($base);
        }

        return $this;
    }

    /**
     * @return Option<string>
     */
    public function distFilename(string $organizationAlias, string $package, string $version, string $ref, string $format): Option
    {
        $dist = new Dist($organizationAlias, $package, $version, $ref, $format);
        if (!$this->distStorage->has($dist)) {
            return Option::none();
        }

        return Option::of($this->distStorage->filename($dist));
    }

    private function filepath(string $organizationAlias, string $packageName): string
    {
        return $this->baseDir.'/'.$organizationAlias.'/p/'.$packageName.'.json';
    }
}
