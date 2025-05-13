<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\Proxy\DistFile;
use Buddy\Repman\Service\Proxy\Metadata;
use InvalidArgumentException;
use JsonException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Munus\Collection\GenericList;
use Munus\Control\Option;
use RuntimeException;
use Throwable;
use function array_filter;
use function array_pop;
use function array_shift;
use function basename;
use function dirname;
use function hash;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function krsort;
use function ksort;
use function ltrim;
use function parse_url;
use function pathinfo;
use function preg_match;
use function preg_replace;
use function rtrim;
use function sprintf;
use function stream_get_contents;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PHP_URL_SCHEME;

final class Proxy
{
    private readonly string $url;

    public function __construct(
        private readonly string $name,
        string $url,
        private readonly FilesystemOperator $filesystem,
        private readonly Downloader $downloader,
    ) {
        $this->url = rtrim($url, '/');
    }

    /**
     * @throws FilesystemException
     */
    public function metadata(string $package): Option
    {
        return $this->fetchMetadataLazy(sprintf('%s/p2/%s.json', $this->url, $package));
    }

    /**
     * @throws FilesystemException|Throwable
     *
     * @return Option<DistFile>
     */
    public function distribution(string $package, string $version, string $ref, string $format): Option
    {
        $path = $this->distPath($package, $ref, $format);
        if (!$this->filesystem->fileExists($path)) {
            foreach ($this->decodeMetadata($package) as $packageData) {
                if (($packageData['dist']['reference'] ?? '') === $ref) {
                    $this->filesystem->writeStream($path, $this->downloader->getContents($packageData['dist']['url'])
                        ->getOrElseThrow(new RuntimeException(
                            sprintf('Failed to download file from %s', $packageData['dist']['url'])))
                    );
                    break;
                }
            }
        }

        try {
            $stream = $this->filesystem->readStream($path);
            $fileSize = $this->filesystem->fileSize($path);

            return $stream !== false && $fileSize !== false ?
                Option::some(new DistFile($stream, $fileSize)) :
                Option::none();
        } catch (UnableToReadFile) {
            return Option::none();
        }
    }

    /**
     * @throws FilesystemException
     */
    public function legacyMetadata(string $package, ?string $hash = null): Option
    {
        return $hash === null ?
            $this->fetchMetadataLazy(sprintf('%s/p/%s.json', $this->url, $package)) :
            $this->fetchMetadata(sprintf('%s/p/%s$%s.json', $this->url, $package, $hash));
    }

    /**
     * @throws FilesystemException
     */
    public function providers(string $version, string $hash): Option
    {
        return $this->fetchMetadata(sprintf('%s/provider/provider-%s$%s.json', $this->url, $version, $hash));
    }

    /**
     * @throws FilesystemException
     *
     * @return Option<Metadata>
     */
    public function latestProvider(): Option
    {
        $providers = [];
        foreach (iterator_to_array($this->filesystem->listContents($this->name.'/provider')) as $file) {
            $path = $file->path();
            $filename = basename($path);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            if ($file->isFile() && $extension === 'json' && str_contains($filename, '$')) {
                $providers[$file->lastModified()] = [
                    'path' => $path,
                    'filename' => $filename,
                ];
            }
        }

        if ($providers === []) {
            return Option::none();
        }

        ksort($providers);
        $provider = array_pop($providers);

        preg_match('/\$(?<hash>.+)$/', $provider['filename'], $matches);
        $hash = $matches['hash'];

        return $this->fetchMetadata(
            sprintf('%s/provider/provider-latest$%s.json', $this->url, $hash),
            $hash
        );
    }

    /**
     * @throws FilesystemException
     *
     * @return GenericList<string>
     */
    public function syncedPackages(): GenericList
    {
        $packages = GenericList::empty();
        foreach (iterator_to_array($this->filesystem->listContents(sprintf('%s/dist', $this->name))) as $vendor) {
            $vendorPath = $vendor->path();
            $vendorBaseName = basename($vendorPath);
            foreach (iterator_to_array($this->filesystem->listContents($vendorPath)) as $package) {
                $packagePath = $package->path();
                $packageBaseName = basename($packagePath);
                $packages = $packages->append($vendorBaseName.'/'.$packageBaseName);
            }
        }

        return $packages;
    }

    /**
     * @throws FilesystemException
     * @throws Throwable
     */
    public function download(string $package, string $version): void
    {
        $lastDist = null;

        foreach ($this->decodeMetadata($package) as $packageData) {
            $lastDist = $packageData['dist'] ?? $lastDist;
            if (!isset($lastDist['reference'])) {
                continue;
            }

            if (!isset($lastDist['type'])) {
                continue;
            }

            if (!isset($lastDist['url'])) {
                continue;
            }

            $path = $this->distPath($package, $lastDist['reference'], $lastDist['type']);
            if ($version === $packageData['version'] && !$this->filesystem->fileExists($path)) {
                $this->filesystem->writeStream($path, $this->downloader->getContents($lastDist['url'])
                    ->getOrElseThrow(new RuntimeException(sprintf('Failed to download file from %s', $lastDist['url'])))
                );
                break;
            }
        }
    }

    /**
     * @throws FilesystemException
     */
    public function removeDist(string $package): void
    {
        if (mb_strlen($package) === 0) {
            throw new InvalidArgumentException('Empty package name');
        }

        $this->filesystem->deleteDirectory(sprintf('%s/dist/%s', $this->name, $package));
    }

    /**
     * @throws FilesystemException
     */
    public function syncMetadata(): void
    {
        foreach ($this->filesystem->listContents($this->name) as $dir) {
            $dirPath = $dir->path();
            $dirBaseName = basename($dirPath);
            if (!in_array($dirBaseName, ['p', 'p2'], true)) {
                continue;
            }

            $this->syncPackagesMetadata(
                array_filter(
                    iterator_to_array($this->filesystem->listContents($dirPath, true)),
                    function ($file) {
                        if (!$file->isFile()) {
                            return false;
                        }

                        $path = $file->path();
                        $filename = basename($path);
                        $extension = pathinfo($filename, PATHINFO_EXTENSION);

                        return $extension === 'json' && !str_contains($filename, '$');
                    }
                )
            );
        }

        $this->downloader->run();
    }

    /**
     * @throws FilesystemException
     * @throws JsonException
     */
    public function updateLatestProviders(): void
    {
        $this->updateLatestProvider(
            array_filter(
                iterator_to_array($this->filesystem->listContents($this->name.'/p', true)),
                function ($file) {
                    if (!$file->isFile()) {
                        return false;
                    }

                    $path = $file->path();
                    $filename = basename($path);
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);

                    return $extension === 'json' && str_contains($filename, '$');
                }
            )
        );
    }

    public function url(): string
    {
        return $this->url;
    }

    private function syncPackagesMetadata(array $files): void
    {
        foreach ($files as $file) {
            $path = $file->path();
            $url = sprintf('%s://%s', parse_url($this->url, PHP_URL_SCHEME), $path);
            $this->downloader->getAsyncContents($url, [], function ($stream) use ($path): void {
                $contents = (string) stream_get_contents($stream);

                $this->filesystem->write($path, $contents);
                if (!str_contains((string) $path, $this->name.'/p2')) {
                    $this->filesystem->write(
                        (string) preg_replace(
                            '/(.+?)(\$\w+|)(\.json)$/',
                            '${1}\$'.hash('sha256', $contents).'.json',
                            (string) $path,
                            1
                        ),
                        $contents
                    );
                }
            });
        }
    }

    /**
     * @throws FilesystemException
     * @throws JsonException
     */
    private function updateLatestProvider(array $files): void
    {
        $latest = [];
        foreach ($files as $file) {
            $path = $file->path();
            $filename = basename((string) $path);
            $dirname = dirname((string) $path);

            preg_match('/(?<name>.+)\$/', $filename, $matches);
            $key = $dirname.'/'.$matches['name'];
            if (!isset($latest[$key])) {
                $latest[$key] = [
                    'path' => $path,
                    'timestamp' => $file->lastModified(),
                ];
                continue;
            }

            if ($file->lastModified() >= $latest[$key]['timestamp']) {
                $this->filesystem->delete($latest[$key]['path']);
                $latest[$key] = [
                    'path' => $path,
                    'timestamp' => $file->lastModified(),
                ];
                continue;
            }

            $this->filesystem->delete($path);
        }

        $providers = [];
        foreach ($latest as $fileData) {
            $path = $fileData['path'];
            preg_match('/'.$this->name.'\/p\/(?<name>.+)\$/', (string) $path, $matches);
            $providers[$matches['name']] = [
                'sha256' => hash('sha256', (string) $this->filesystem->read($path)),
            ];
        }

        $contents = json_encode([
            'providers' => $providers,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $basePath = sprintf('%s/provider', $this->name);

        $oldProviders = [];
        foreach (iterator_to_array($this->filesystem->listContents($basePath)) as $file) {
            $path = $file->path();
            $filename = basename($path);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            if (!$file->isFile()) {
                continue;
            }

            if ($extension !== 'json') {
                continue;
            }

            $oldProviders[$file->lastModified()] = [
                'path' => $path,
            ];
        }

        krsort($oldProviders);
        array_shift($oldProviders);

        foreach ($oldProviders as $file) {
            $this->filesystem->delete($file['path']);
        }

        $this->filesystem->write(
            sprintf('%s/provider-latest$%s.json', $basePath, hash('sha256', $contents)),
            $contents
        );
    }

    /**
     * @throws FilesystemException
     */
    private function decodeMetadata(string $package): array
    {
        /** @var Metadata $metadata */
        $metadata = $this->metadata($package)->getOrElse(Metadata::fromString('[]'));
        $metadata = json_decode((string) stream_get_contents($metadata->stream()), true);

        return is_array($metadata) ? ($metadata['packages'][$package] ?? []) : [];
    }

    /**
     * @throws FilesystemException
     *
     * @return Option<Metadata>
     */
    private function fetchMetadata(string $url, ?string $hash = null): Option
    {
        $path = $this->metadataPath($url);
        if (!$this->filesystem->fileExists($path)) {
            return Option::none();
        }

        $stream = $this->filesystem->readStream($path);
        if ($stream === false) {
            return Option::none();
        }

        $fileSize = $this->filesystem->fileSize($path);

        return Option::some(new Metadata(
            (int) $this->filesystem->lastModified($path),
            $stream,
            $fileSize === false ? 0 : $fileSize,
            $hash
        ));
    }

    /**
     * @throws FilesystemException
     *
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

        $stream = $this->filesystem->readStream($path);
        if ($stream === false) {
            return Option::none();
        }

        $fileSize = $this->filesystem->fileSize($path);

        return Option::some(new Metadata(
            (int) $this->filesystem->lastModified($path),
            $stream,
            $fileSize === false ? 0 : $fileSize
        ));
    }

    private function distPath(string $package, string $ref, string $format): string
    {
        return sprintf(
            '%s/dist/%s/%s.%s',
            (string) parse_url($this->url, PHP_URL_HOST),
            $package,
            $ref,
            $format
        );
    }

    private function metadataPath(string $url): string
    {
        return parse_url($url, PHP_URL_HOST).'/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');
    }
}
