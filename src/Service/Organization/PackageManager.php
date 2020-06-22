<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Munus\Control\Option;

class PackageManager
{
    private FilesystemInterface $repoStorage;
    private Storage $distStorage;

    public function __construct(FilesystemInterface $repoStorage, Storage $distStorage)
    {
        $this->repoStorage = $repoStorage;
        $this->distStorage = $distStorage;
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

            try {
                $contents = $this->repoStorage->read($filepath);
            } catch (FileNotFoundException $e) {
                continue;
            }

            $json = unserialize($contents, ['allowed_classes' => false]);
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
        $this->repoStorage->put($filepath, serialize($json));
    }

    public function removeProvider(string $organizationAlias, string $packageName): self
    {
        $file = $this->filepath($organizationAlias, $packageName);

        try {
            $this->repoStorage->delete($file);
        } catch (FileNotFoundException $e) {
        }

        return $this;
    }

    public function removeDist(string $organizationAlias, string $packageName): self
    {
        $this->repoStorage->deleteDir("{$organizationAlias}/dist/{$packageName}");

        return $this;
    }

    public function removeOrganizationDir(string $organizationAlias): self
    {
        if ($this->repoStorage->has($organizationAlias)) {
            $this->repoStorage->deleteDir($organizationAlias);
        }

        return $this;
    }

    private function filepath(string $organizationAlias, string $packageName): string
    {
        return "{$organizationAlias}/p/{$packageName}.json";
    }
}
