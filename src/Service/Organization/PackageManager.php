<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use function array_filter;
use function array_merge;
use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Composer\Semver\VersionParser;
use DateTimeImmutable;
use function dirname;
use function glob;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Munus\Control\Option;
use function serialize;
use function unserialize;

class PackageManager
{
    private Storage $distStorage;
    private FilesystemInterface $repoStorage;
    private VersionParser $versionParser;

    public function __construct(Storage $distStorage, FilesystemInterface $repoStorage)
    {
        $this->distStorage = $distStorage;
        $this->repoStorage = $repoStorage;
        $this->versionParser = new VersionParser();
    }

    /**
     * @param PackageName[] $packages
     *
     * @return array{\DateTimeImmutable|null, mixed[]}
     */
    public function findProviders(string $organizationAlias, array $packages): array
    {
        $data = [];
        $lastModified = null;

        foreach ($packages as $package) {
            $filepath = $this->filepath($organizationAlias, $package->name());
            if (!$this->repoStorage->has($filepath)) {
                continue;
            }

            $fileModifyDate = (new DateTimeImmutable())->setTimestamp((int) $this->repoStorage->getTimestamp($filepath));

            if ($fileModifyDate > $lastModified) {
                $lastModified = $fileModifyDate;
            }

            $json = unserialize(
                (string) $this->repoStorage->read($filepath), ['allowed_classes' => false]
            );
            $data[] = $json['packages'] ?? [];
        }

        return [
            $lastModified,
            array_merge(...$data),
        ];
    }

    /**
     * @param mixed[] $json
     */
    public function saveProvider(array $json, string $organizationAlias, string $packageName): void
    {
        $filepath = $this->filepath($organizationAlias, $packageName);

        $dir = dirname($filepath);
        $this->repoStorage->createDir($dir);

        $this->repoStorage->write($filepath, serialize($json));
    }

    public function removeProvider(string $organizationAlias, string $packageName): self
    {
        $file = $this->filepath($organizationAlias, $packageName);
        $this->removeFile($file);

        return $this;
    }

    public function removeDist(string $organizationAlias, string $packageName): self
    {
        $distDir = $organizationAlias.'/dist/'.$packageName;
        $this->repoStorage->deleteDir($distDir);

        return $this;
    }

    public function removeVersionDists(string $organizationAlias, string $packageName, string $version, string $format, string $excludeRef): self
    {
        $baseFilename = $organizationAlias.'/dist/'.$packageName.'/'.$this->versionParser->normalize($version).'_';

        $filesToDelete = array_filter(
            (array) glob($baseFilename.'*.'.$format),
            fn ($file) => $file !== $baseFilename.$excludeRef.'.'.$format
        );

        foreach ($filesToDelete as $fileName) {
            if (false === $fileName) {
                continue;
            }
            $this->removeFile($fileName);
        }

        return $this;
    }

    public function removeOrganizationDir(string $organizationAlias): self
    {
        $this->repoStorage->deleteDir($organizationAlias);

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

    /**
     * @return Option<resource> Handle for a file
     */
    public function getDistFileReference(
        string $fileName
    ): Option {
        $fileResource = $this->repoStorage->readStream($fileName);
        if (false === $fileResource) {
            return Option::none();
        }

        return Option::some($fileResource);
    }

    private function filepath(string $organizationAlias, string $packageName): string
    {
        return $organizationAlias.'/p/'.$packageName.'.json';
    }

    private function removeFile(string $fileName): void
    {
        try {
            $this->repoStorage->delete($fileName);
        } catch (FileNotFoundException $ignored) {
        }
    }
}
