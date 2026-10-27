<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Composer\Semver\VersionParser;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Munus\Control\Option;

class PackageManager
{
    private Storage $distStorage;
    private FilesystemOperator $repoFilesystem;
    private VersionParser $versionParser;

    public function __construct(Storage $distStorage, FilesystemOperator $repoFilesystem)
    {
        $this->distStorage = $distStorage;
        $this->repoFilesystem = $repoFilesystem;
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
            if (!$this->repoFilesystem->fileExists($filepath)) {
                continue;
            }

            $fileModifyDate = (new \DateTimeImmutable())->setTimestamp($this->repoFilesystem->lastModified($filepath));

            if ($fileModifyDate > $lastModified) {
                $lastModified = $fileModifyDate;
            }

            $json = \unserialize(
                $this->repoFilesystem->read($filepath), ['allowed_classes' => false]
            );
            $data[] = $json['packages'] ?? [];
        }

        return [
            $lastModified,
            \array_merge(...$data),
        ];
    }

    /**
     * @param mixed[] $json
     */
    public function saveProvider(array $json, string $organizationAlias, string $packageName): void
    {
        $this->repoFilesystem->write($this->filepath($organizationAlias, $packageName), \serialize($json));
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
        $this->repoFilesystem->deleteDirectory($distDir);

        return $this;
    }

    public function removeVersionDists(string $organizationAlias, string $packageName, string $version, string $format, string $excludeRef): self
    {
        $distDir = $organizationAlias.'/dist/'.$packageName;
        $baseFilename = $distDir.'/'.$this->versionParser->normalize($version).'_';
        $keep = $baseFilename.$excludeRef.'.'.$format;

        if (!$this->repoFilesystem->directoryExists($distDir)) {
            return $this;
        }

        $filesToDelete = $this->repoFilesystem->listContents($distDir)->filter(
            fn (StorageAttributes $file) => $file->isFile()
                && \strpos($file->path(), $baseFilename) === 0
                && \substr($file->path(), -\strlen('.'.$format)) === '.'.$format
                && $file->path() !== $keep
        );

        foreach ($filesToDelete as $file) {
            $this->removeFile($file->path());
        }

        return $this;
    }

    public function removeOrganizationDir(string $organizationAlias): self
    {
        $this->repoFilesystem->deleteDirectory($organizationAlias);

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
        string $fileName,
    ): Option {
        try {
            return Option::some($this->repoFilesystem->readStream($fileName));
        } catch (FilesystemException $exception) {
            return Option::none();
        }
    }

    /**
     * The composer.lock of a version installed from source (no dist archive), kept next to the dists
     * as <version>_<ref>.lock for the security scan.
     */
    public function saveSourceLock(string $organizationAlias, string $packageName, string $version, string $ref, string $content): void
    {
        $this->repoFilesystem->write($this->sourceLockFilename($organizationAlias, $packageName, $version, $ref), $content);
    }

    /**
     * Removes the kept lock files of every other version and reference: only the versions the scan
     * reads need one, and the latest release moves on.
     *
     * @param array<string,string> $refsByVersion
     */
    public function keepOnlySourceLocks(string $organizationAlias, string $packageName, array $refsByVersion): void
    {
        $distDir = $organizationAlias.'/dist/'.$packageName;
        if (!$this->repoFilesystem->directoryExists($distDir)) {
            return;
        }

        $keep = [];
        foreach ($refsByVersion as $version => $ref) {
            $keep[$this->sourceLockFilename($organizationAlias, $packageName, $version, $ref)] = true;
        }

        foreach ($this->repoFilesystem->listContents($distDir) as $file) {
            if ($file->isFile() && \str_ends_with($file->path(), '.lock') && !isset($keep[$file->path()])) {
                $this->removeFile($file->path());
            }
        }
    }

    public function sourceLock(string $organizationAlias, string $packageName, string $version, string $ref): ?string
    {
        try {
            return $this->repoFilesystem->read($this->sourceLockFilename($organizationAlias, $packageName, $version, $ref));
        } catch (FilesystemException) {
            return null;
        }
    }

    private function sourceLockFilename(string $organizationAlias, string $packageName, string $version, string $ref): string
    {
        return $this->distStorage->filename(new Dist($organizationAlias, $packageName, $this->versionParser->normalize($version), $ref, 'lock'));
    }

    private function filepath(string $organizationAlias, string $packageName): string
    {
        return $organizationAlias.'/p/'.$packageName.'.json';
    }

    private function removeFile(string $fileName): void
    {
        try {
            $this->repoFilesystem->delete($fileName);
        } catch (FilesystemException $ignored) {
        }
    }
}
