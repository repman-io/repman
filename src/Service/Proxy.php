<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

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
        $this->url = rtrim($url, '/');
        $this->filesystem = $proxyFilesystem;
        $this->downloader = $downloader;
    }

    /**
     * @return Option<Metadata>
     */
    public function metadata(string $package): Option
    {
        return $this->fetchMetadata(sprintf('%s/p2/%s.json', $this->url, $package));
    }

    /**
     * @return Option<resource>
     */
    public function distribution(string $package, string $version, string $ref, string $format): Option
    {
        $path = $this->distPath($package, $ref, $format);
        if (!$this->filesystem->has($path)) {
            foreach ($this->decodeMetadata($package) as $packageData) {
                if (($packageData['dist']['reference'] ?? '') === $ref) {
                    $this->filesystem->write($path, $this->downloader->getContents($packageData['dist']['url'])
                        ->getOrElseThrow(new \RuntimeException(sprintf('Failed to download file from %s', $packageData['dist']['url'])))
                    );
                    break;
                }
            }
        }

        try {
            $stream = $this->filesystem->readStream($path);

            return $stream !== false ? Option::some($stream) : Option::none();
        } catch (FileNotFoundException $exception) {
            return Option::none();
        }
    }

    /**
     * @return Option<Metadata>
     */
    public function legacyMetadata(string $package): Option
    {
        return $this->fetchMetadata(sprintf('%s/p/%s.json', $this->url, $package));
    }

    /**
     * @return GenericList<string>
     */
    public function syncedPackages(): GenericList
    {
        $packages = GenericList::empty();
        foreach ($this->filesystem->listContents(sprintf('%s/dist', $this->name)) as $vendor) {
            foreach ($this->filesystem->listContents($vendor['path']) as $package) {
                $packages = $packages->append($vendor['basename'].'/'.$package['basename']);
            }
        }

        return $packages;
    }

    public function downloadByVersion(string $package, string $version): void
    {
        $lastDist = null;

        foreach ($this->decodeMetadata($package) as $packageData) {
            $lastDist = $packageData['dist'] ?? $lastDist;
            if ($version === $packageData['version']) {
                $this->filesystem->write($this->distPath($package, $lastDist['reference'], $lastDist['type']), $this->downloader->getContents($lastDist['url'])
                    ->getOrElseThrow(new \RuntimeException(sprintf('Failed to download file from %s', $lastDist['url'])))
                );
                break;
            }
        }
    }

    /**
     * @return mixed[]
     */
    private function decodeMetadata(string $package): array
    {
        /** @var Metadata $metadata */
        $metadata = $this->metadata($package)->getOrElse(Metadata::fromString('[]'));
        $metadata = json_decode((string) stream_get_contents($metadata->stream()), true);

        return is_array($metadata) ? ($metadata['packages'][$package] ?? []) : [];
    }

    /**
     * @return Option<Metadata>
     */
    private function fetchMetadata(string $url): Option
    {
        $path = $this->metadataPath($url);
        if (!$this->filesystem->has($path)) {
            $metadata = $this->downloader->getContents($url)->getOrNull();
            if ($metadata === null) {
                return Option::none();
            }
            $this->filesystem->write($path, $metadata);
        }

        $stream = $this->filesystem->readStream($path);
        if ($stream === false) {
            return Option::none();
        }

        return Option::some(new Metadata(
            (int) $this->filesystem->getTimestamp($path),
            $stream
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
        return (string) parse_url($url, PHP_URL_HOST).'/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');
    }
}
