<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\Proxy\DistFile;
use Buddy\Repman\Service\Proxy\Metadata;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Munus\Collection\GenericList;
use Munus\Control\Option;

final class Proxy
{
    private const LAST_MODIFIED_SUFFIX = '.last-modified';

    private string $url;
    private string $metadataUrl;
    private string $name;
    private FilesystemOperator $filesystem;
    private Downloader $downloader;

    /**
     * @param string|null $metadataUrl where metadata is fetched from when that is not $url itself
     */
    public function __construct(
        string $name,
        string $url,
        FilesystemOperator $proxyFilesystem,
        Downloader $downloader,
        ?string $metadataUrl = null,
    ) {
        $this->name = $name;
        $this->url = \rtrim($url, '/');
        $this->metadataUrl = \rtrim($metadataUrl ?? $url, '/');
        $this->filesystem = $proxyFilesystem;
        $this->downloader = $downloader;
    }

    /**
     * @return Option<Metadata>
     */
    public function metadata(string $package): Option
    {
        return $this->fetchMetadataLazy(\sprintf('%s/p2/%s.json', $this->metadataUrl, $package));
    }

    /**
     * @return Option<DistFile>
     */
    public function distribution(string $package, string $version, string $ref, string $format): Option
    {
        $path = $this->distPath($package, $ref, $format);
        if (!$this->filesystem->fileExists($path)) {
            foreach ($this->decodeMetadata($package) as $packageData) {
                if (($packageData['dist']['reference'] ?? '') === $ref) {
                    $this->filesystem->writeStream($path, $this->downloader->getContents($packageData['dist']['url'])
                        ->getOrElseThrow(new \RuntimeException(
                            \sprintf('Failed to download file from %s', $packageData['dist']['url'])))
                    );
                    break;
                }
            }
        }

        try {
            return Option::some(new DistFile($this->filesystem->readStream($path), $this->filesystem->fileSize($path)));
        } catch (FilesystemException $exception) {
            return Option::none();
        }
    }

    /**
     * @return Option<Metadata>
     */
    public function legacyMetadata(string $package, ?string $hash = null): Option
    {
        return $hash === null ?
            $this->fetchMetadataLazy(\sprintf('%s/p/%s.json', $this->metadataUrl, $package)) :
            $this->fetchMetadata(\sprintf('%s/p/%s$%s.json', $this->metadataUrl, $package, $hash));
    }

    /**
     * @return Option<Metadata>
     */
    public function providers(string $version, string $hash): Option
    {
        return $this->fetchMetadata(\sprintf('%s/provider/provider-%s$%s.json', $this->metadataUrl, $version, $hash));
    }

    /**
     * @return Option<Metadata>
     */
    public function latestProvider(): Option
    {
        $providers = [];
        foreach ($this->jsonFiles($this->name.'/provider', false) as $file) {
            if (\strpos(self::filename($file), '$') !== false) {
                $providers[] = $file;
            }
        }

        if ($providers === []) {
            return Option::none();
        }

        $provider = \array_pop($providers);

        if (\preg_match('/\$(?<hash>.+)$/', self::filename($provider), $matches) !== 1) {
            return Option::none();
        }
        $hash = $matches['hash'];

        return $this->fetchMetadata(
            \sprintf('%s/provider/provider-latest$%s.json', $this->metadataUrl, $hash),
            $hash
        );
    }

    /**
     * @return GenericList<string>
     */
    public function syncedPackages(): GenericList
    {
        $packages = GenericList::empty();
        foreach ($this->filesystem->listContents(\sprintf('%s/dist', $this->name)) as $vendor) {
            if (!$vendor->isDir()) {
                continue;
            }
            foreach ($this->filesystem->listContents($vendor->path()) as $package) {
                if ($package->isDir()) {
                    $packages = $packages->append(\basename($vendor->path()).'/'.\basename($package->path()));
                }
            }
        }

        return $packages;
    }

    public function download(string $package, string $version): void
    {
        $lastDist = null;

        foreach ($this->decodeMetadata($package) as $packageData) {
            $lastDist = $packageData['dist'] ?? $lastDist;
            if (!isset($lastDist['reference']) || !isset($lastDist['type']) || !isset($lastDist['url'])) {
                continue;
            }

            $path = $this->distPath($package, $lastDist['reference'], $lastDist['type']);
            if ($version === $packageData['version'] && !$this->filesystem->fileExists($path)) {
                $this->filesystem->writeStream($path, $this->downloader->getContents($lastDist['url'])
                    ->getOrElseThrow(new \RuntimeException(\sprintf('Failed to download file from %s', $lastDist['url'])))
                );
                break;
            }
        }
    }

    public function removeDist(string $package): void
    {
        if (mb_strlen($package) === 0) {
            throw new \InvalidArgumentException('Empty package name');
        }

        $this->filesystem->deleteDirectory(\sprintf('%s/dist/%s', $this->name, $package));
    }

    /**
     * Refreshes the cached Composer 2 metadata. Only files changed upstream are downloaded: Packagist
     * answers the conditional request with 304 otherwise. Composer 1 (p/) metadata is no longer
     * published by Packagist, so it is served from cache as is.
     */
    public function syncMetadata(): void
    {
        if ($this->filesystem->directoryExists($this->name.'/p2')) {
            $this->syncPackagesMetadata(
                \array_filter(
                    $this->jsonFiles($this->name.'/p2', true),
                    fn (StorageAttributes $file) => \strpos(self::filename($file), '$') === false)
            );
        }
        $this->downloader->run();
    }

    public function updateLatestProviders(): void
    {
        $this->updateLatestProvider(
            \array_filter(
                $this->jsonFiles($this->name.'/p', true),
                fn (StorageAttributes $file) => \strpos(self::filename($file), '$') !== false)
        );
    }

    public function url(): string
    {
        return $this->url;
    }

    /**
     * @param StorageAttributes[] $files
     */
    private function syncPackagesMetadata(array $files): void
    {
        foreach ($files as $file) {
            $path = $file->path();
            $url = $this->metadataUrl.\substr($path, \strlen($this->name));
            // Packagist answers 304 only when this is exactly the Last-Modified it sent last time
            $lastModifiedPath = $path.self::LAST_MODIFIED_SUFFIX;
            $headers = $this->filesystem->fileExists($lastModifiedPath)
                ? ['If-Modified-Since' => $this->filesystem->read($lastModifiedPath)]
                : [];
            $this->downloader->getAsyncContents($url, $headers, function ($stream, string $lastModified = '') use ($path, $lastModifiedPath): void {
                $contents = (string) \stream_get_contents($stream);
                if ($contents === '') {
                    return;
                }

                $this->filesystem->write($path, $contents);
                if ($lastModified !== '') {
                    $this->filesystem->write($lastModifiedPath, $lastModified);
                }
                if (strpos($path, $this->name.'/p2') === false) {
                    $this->filesystem->write(
                        (string) \preg_replace(
                            '/(.+?)(\$\w+|)(\.json)$/',
                            '${1}\$'.\hash('sha256', $contents).'.json',
                            $path,
                            1
                        ),
                        $contents
                    );
                }
            });
        }
    }

    /**
     * @param StorageAttributes[] $files
     */
    private function updateLatestProvider(array $files): void
    {
        $latest = [];
        foreach ($files as $file) {
            if (\preg_match('/(?<name>.+)\$/', self::filename($file), $matches) !== 1) {
                continue;
            }
            $key = \dirname($file->path()).'/'.$matches['name'];
            if (!isset($latest[$key])) {
                $latest[$key] = $file;
                continue;
            }

            if ((int) $file->lastModified() >= (int) $latest[$key]->lastModified()) {
                $this->filesystem->delete($latest[$key]->path());
                $latest[$key] = $file;
                continue;
            }

            $this->filesystem->delete($file->path());
        }

        $providers = [];
        foreach ($latest as $file) {
            $path = $file->path();
            if (\preg_match('/'.\preg_quote($this->name, '/').'\/p\/(?<name>.+)\$/', $path, $matches) !== 1) {
                continue;
            }
            $providers[$matches['name']] = [
                'sha256' => \hash('sha256', $this->filesystem->read($path)),
            ];
        }

        $contents = \json_encode([
            'providers' => $providers,
        ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
        $basePath = \sprintf('%s/provider', $this->name);

        $oldProviders = $this->jsonFiles($basePath, false);
        \array_pop($oldProviders);

        foreach ($oldProviders as $file) {
            $this->filesystem->delete($file->path());
        }

        $this->filesystem->write(
            \sprintf('%s/provider-latest$%s.json', $basePath, \hash('sha256', $contents)),
            $contents
        );
    }

    /**
     * @return mixed[]
     */
    private function decodeMetadata(string $package): array
    {
        /** @var Metadata $metadata */
        $metadata = $this->metadata($package)->getOrElse(Metadata::fromString('[]'));
        $metadata = \json_decode((string) \stream_get_contents($metadata->stream()), true);

        return \is_array($metadata) ? ($metadata['packages'][$package] ?? []) : [];
    }

    /**
     * @return Option<Metadata>
     */
    private function fetchMetadata(string $url, ?string $hash = null): Option
    {
        $path = $this->metadataPath($url);
        if (!$this->filesystem->fileExists($path)) {
            return Option::none();
        }

        return $this->readMetadata($path, $hash);
    }

    /**
     * @return Option<Metadata>
     */
    private function fetchMetadataLazy(string $url): Option
    {
        $path = $this->metadataPath($url);
        if (!$this->filesystem->fileExists($path)) {
            $metadata = $this->downloader->getContents($url)->getOrNull();
            if ($metadata === null) {
                return Option::none();
            }
            $this->filesystem->writeStream($path, $metadata);
        }

        return $this->readMetadata($path);
    }

    /**
     * @return Option<Metadata>
     */
    private function readMetadata(string $path, ?string $hash = null): Option
    {
        try {
            return Option::some(new Metadata(
                $this->filesystem->lastModified($path),
                $this->filesystem->readStream($path),
                $this->filesystem->fileSize($path),
                $hash
            ));
        } catch (FilesystemException $exception) {
            return Option::none();
        }
    }

    /**
     * JSON files in a directory, oldest first.
     *
     * @return StorageAttributes[]
     */
    private function jsonFiles(string $path, bool $deep): array
    {
        $files = $this->filesystem->listContents($path, $deep)
            ->filter(fn (StorageAttributes $file) => $file->isFile() && \pathinfo($file->path(), \PATHINFO_EXTENSION) === 'json')
            ->sortByPath()
            ->toArray();

        \usort($files, fn (StorageAttributes $a, StorageAttributes $b) => (int) $a->lastModified() <=> (int) $b->lastModified());

        return $files;
    }

    private static function filename(StorageAttributes $file): string
    {
        return \pathinfo($file->path(), \PATHINFO_FILENAME);
    }

    private function distPath(string $package, string $ref, string $format): string
    {
        return \sprintf(
            '%s/dist/%s/%s.%s',
            (string) \parse_url($this->url, \PHP_URL_HOST),
            $package,
            $ref,
            $format
        );
    }

    /**
     * Metadata is stored under the proxy's name whichever host it came from.
     */
    private function metadataPath(string $url): string
    {
        return $this->name.'/'.\ltrim((string) \parse_url($url, \PHP_URL_PATH), '/');
    }
}
