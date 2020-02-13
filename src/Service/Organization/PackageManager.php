<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Composer\Semver\VersionParser;
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
     * @param Package[] $packages
     *
     * @return mixed[]
     */
    public function findProviders(string $organizationAlias, array $packages): array
    {
        $data = [];
        foreach ($packages as $package) {
            if ($package->name() === null) {
                continue;
            }

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

    /**
     * @return Option<string>
     */
    public function distFilename(string $organizationAlias, string $package, string $version, string $ref, string $format): Option
    {
        $dist = new Dist($organizationAlias, $package, $version, $ref, $format);
        if (!$this->distStorage->has($dist)) {
            $filepath = $this->filepath($organizationAlias, $package);
            if (!is_readable($filepath)) {
                return Option::none();
            }
            $json = unserialize((string) file_get_contents($filepath));
            $parser = new VersionParser();
            foreach ($json['packages'][$package] as $packageVersion) {
                if (!isset($packageVersion['version_normalized'])) {
                    $packageVersion['version_normalized'] = $parser->normalize($packageVersion['version']);
                }

                if ($packageVersion['version_normalized'] === $version && isset($packageVersion['dist']['url'])) {
                    $this->distStorage->download($packageVersion['dist']['url'], $dist);
                }
            }
        }

        $distFilename = $this->distStorage->filename($dist);

        return Option::when(file_exists($distFilename), $distFilename);
    }

    private function filepath(string $organizationAlias, string $packageName): string
    {
        return $this->baseDir.'/'.$organizationAlias.'/p/'.$packageName.'.json';
    }
}
