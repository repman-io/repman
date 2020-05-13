<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Service\AtomicFile;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Munus\Control\Option;
use Symfony\Component\Filesystem\Filesystem;

class PackageManager
{
    private Storage $distStorage;
    private string $baseDir;
    private Filesystem $filesystem;

    public function __construct(Storage $distStorage, string $baseDir, Filesystem $filesystem)
    {
        $this->distStorage = $distStorage;
        $this->baseDir = $baseDir;
        $this->filesystem = $filesystem;
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

        AtomicFile::write($filepath, serialize($json));
    }

    public function removeProvider(string $organizationAlias, string $packageName): self
    {
        $file = $this->filepath($organizationAlias, $packageName);
        $names = explode('/', $packageName);
        $distDir = $this->baseDir.'/'.$organizationAlias.'/dist/'.$names[0];

        if (is_file($file)) {
            $this->filesystem->remove(dirname($file));
        }

        if (is_dir($distDir)) {
            $this->filesystem->remove($distDir);
        }

        return $this;
    }

    public function removeOrganizationDir(string $organizationAlias): self
    {
        if (is_dir($base = $this->baseDir.'/'.$organizationAlias)) {
            $this->filesystem->remove($base);
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
