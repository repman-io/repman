<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\Proxy\DistFile;
use Buddy\Repman\Service\Proxy\Metadata;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Munus\Collection\GenericList;
use Munus\Control\Option;

final class Proxy
{
    private string $url;
    private string $name;
    private FilesystemInterface $filesystem;
    private Downloader $downloader;

    public function __construct(
        string $name,
        string $url,
        FilesystemInterface $proxyFilesystem,
        Downloader $downloader
    ) {
        $this->name = $name;
        $this->url = \rtrim($url, '/');
        $this->filesystem = $proxyFilesystem;
        $this->downloader = $downloader;
    }

    /**
     * @return Option<Metadata>
     */
    public function metadata(string $package): Option
    {
        return $this->fetchMetadataLazy(\sprintf('%s/p2/%s.json', $this->url, $package));
    }

    /**
     * @return Option<DistFile>
     */
    public function distribution(string $package, string $version, string $ref, string $format): Option
    {
        $path = $this->distPath($package, $ref, $format);
        if (!$this->filesystem->has($path)) {
            foreach ($this->decodeMetadata($package) as $packageData) {
                if (($packageData['dist']['reference'] ?? '') === $ref) {
                    $this->filesystem->putStream($path, $this->downloader->getContents($packageData['dist']['url'])
                        ->getOrElseThrow(new \RuntimeException(
                                             \sprintf('Failed to download file from %s', $packageData['dist']['url'])))
                    );
                    break;
                }
            }
        }

        try {
            $stream = $this->filesystem->readStream($path);
            $fileSize = $this->filesystem->getSize($path);

            return $stream !== false && $fileSize !== false ?
                Option::some(new DistFile($stream, $fileSize)) :
                Option::none();
        } catch (FileNotFoundException $exception) {
            return Option::none();
        }
    }

    /**
     * @return Option<Metadata>
     */
    public function legacyMetadata(string $package, ?string $hash = null): Option
    {
        return $hash === null ?
            $this->fetchMetadataLazy(\sprintf('%s/p/%s.json', $this->url, $package)) :
            $this->fetchMetadata(\sprintf('%s/p/%s$%s.json', $this->url, $package, $hash));
    }

    /**
     * @return Option<Metadata>
     */
    public function providers(string $version, string $hash): Option
    {
        return $this->fetchMetadata(\sprintf('%s/provider/provider-%s$%s.json', $this->url, $version, $hash));
    }

    /**
     * @return Option<Metadata>
     */
    public function latestProvider(): Option
    {
        $providers = [];
        foreach ($this->filesystem->listContents($this->name.'/provider') as $file) {
            if ($file['type'] === 'file' && $file['extension'] === 'json' && \strpos($file['filename'], '$') !== false) {
                $providers[$file['timestamp']] = $file;
            }
        }

        if ($providers === []) {
            return Option::none();
        }

        \ksort($providers);
        $provider = \array_pop($providers);

        \preg_match('/\$(?<hash>.+)$/', $provider['filename'], $matches);
        $hash = $matches['hash'];

        return $this->fetchMetadata(
            \sprintf('%s/provider/provider-latest$%s.json', $this->url, $hash),
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
            foreach ($this->filesystem->listContents($vendor['path']) as $package) {
                $packages = $packages->append($vendor['basename'].'/'.$package['basename']);
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
            if ($version === $packageData['version'] && !$this->filesystem->has($path)) {
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

        $this->filesystem->deleteDir(\sprintf('%s/dist/%s', $this->name, $package));
    }

    public function syncMetadata(): void
    {
        foreach ($this->filesystem->listContents($this->name) as $dir) {
            if (!\in_array($dir['basename'], ['p', 'p2'], true)) {
                continue;
            }

            $this->syncPackagesMetadata(
                \array_filter(
                $this->filesystem->listContents($dir['path'], true),
                fn (array $file) => $file['type'] === 'file' && $file['extension'] === 'json' && \strpos($file['filename'], '$') === false)
            );
        }
        $this->downloader->run();
    }

    public function updateLatestProviders(): void
    {
        $this->updateLatestProvider(
            \array_filter(
            $this->filesystem->listContents($this->name.'/p', true),
            fn (array $file) => $file['type'] === 'file' && $file['extension'] === 'json' && \strpos($file['filename'], '$') !== false)
        );
    }

    public function url(): string
    {
        return $this->url;
    }

    /**
     * @param mixed[] $files
     */
    private function syncPackagesMetadata(array $files): void
    {
        foreach ($files as $file) {
            $url = \sprintf('%s://%s', \parse_url($this->url, \PHP_URL_SCHEME), $file['path']);
            $this->downloader->getAsyncContents($url, [], function ($stream) use ($file): void {
                $path = $file['path'];
                $contents = (string) \stream_get_contents($stream);

                $this->filesystem->put($path, $contents);
                if (strpos($path, $this->name.'/p2') === false) {
                    $this->filesystem->put(
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
     * @param mixed[] $files
     */
    private function updateLatestProvider(array $files): void
    {
        $latest = [];
        foreach ($files as $file) {
            \preg_match('/(?<name>.+)\$/', $file['filename'], $matches);
            $key = $file['dirname'].'/'.$matches['name'];
            if (!isset($latest[$key])) {
                $latest[$key] = $file;
                continue;
            }

            if ($file['timestamp'] >= $latest[$key]['timestamp']) {
                $this->filesystem->delete($latest[$key]['path']);
                $latest[$key] = $file;
                continue;
            }

            $this->filesystem->delete($file['path']);
        }

        $providers = [];
        foreach ($latest as $file) {
            $path = $file['path'];
            \preg_match('/'.$this->name.'\/p\/(?<name>.+)\$/', $path, $matches);
            $providers[$matches['name']] = [
                'sha256' => \hash('sha256', (string) $this->filesystem->read($path)),
            ];
        }

        $contents = \json_encode([
            'providers' => $providers,
        ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
        $basePath = \sprintf('%s/provider', $this->name);

        $oldProviders = [];
        foreach ($this->filesystem->listContents($basePath) as $file) {
            if ($file['type'] !== 'file' || $file['extension'] !== 'json') {
                continue;
            }

            $oldProviders[$file['timestamp']] = $file;
        }

        \krsort($oldProviders);
        \array_shift($oldProviders);

        foreach ($oldProviders as $file) {
            $this->filesystem->delete($file['path']);
        }

        $this->filesystem->put(
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
        if (!$this->filesystem->has($path)) {
            return Option::none();
        }

        $stream = $this->filesystem->readStream($path);
        if ($stream === false) {
            return Option::none();
        }

        $fileSize = $this->filesystem->getSize($path);

        return Option::some(new Metadata(
            (int) $this->filesystem->getTimestamp($path),
            $stream,
            $fileSize === false ? 0 : $fileSize,
            $hash
        ));
    }

    /**
     * @return Option<Metadata>
     */
    private function fetchMetadataLazy(string $url): Option
    {
        $path = $this->metadataPath($url);
        if (!$this->filesystem->has($path)) {
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

        $fileSize = $this->filesystem->getSize($path);

        return Option::some(new Metadata(
            (int) $this->filesystem->getTimestamp($path),
            $stream,
            $fileSize === false ? 0 : $fileSize
        ));
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

    private function metadataPath(string $url): string
    {
        return \parse_url($url, \PHP_URL_HOST).'/'.\ltrim((string) \parse_url($url, \PHP_URL_PATH), '/');
    }
}
