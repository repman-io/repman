<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Dist;

use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Downloader;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Munus\Control\Option;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Storage
{
    private Downloader $downloader;
    private FilesystemInterface $repoFilesystem;

    public function __construct(Downloader $downloader, FilesystemInterface $repoFilesystem)
    {
        $this->downloader = $downloader;
        $this->repoFilesystem = $repoFilesystem;
    }

    public function has(Dist $dist): bool
    {
        return $this->repoFilesystem->has($this->filename($dist));
    }

    /**
     * @param string[] $headers
     */
    public function download(string $url, Dist $dist, array $headers = []): void
    {
        if ($this->has($dist)) {
            return;
        }

        $filename = $this->filename($dist);

        $this->repoFilesystem->writeStream(
            $filename,
            $this->downloader->getContents(
                $url,
                $headers,
                function () use ($url): void {
                    throw new NotFoundHttpException(\sprintf('File not found at %s', $url));
                }
            )->getOrElseThrow(
                new \RuntimeException(\sprintf('Failed to download %s from %s', $dist->package(), $url))
            )
        );
    }

    public function remove(Dist $dist): void
    {
        $filename = $this->filename($dist);
        if ($this->repoFilesystem->has($filename)) {
            $this->repoFilesystem->delete($filename);
        }
    }

    public function filename(Dist $dist): string
    {
        $filename = \sprintf(
            '%s/dist/%s/%s',
            $dist->repo(),
            $dist->package(),
            $dist->version()
        );

        if ($dist->ref() !== '') {
            $filename .= '_'.$dist->ref();
        }

        $filename .= '.'.$dist->format();

        return $filename;
    }

    public function size(Dist $dist): int
    {
        $filename = $this->filename($dist);
        if ($this->repoFilesystem->has($filename)) {
            /* @phpstan-ignore-next-line - will always return int because file exists */
            return $this->repoFilesystem->getSize($filename);
        }

        return 0;
    }

    /**
     * @return Option<string>
     */
    public function getLocalFileForDist(Dist $dist): Option
    {
        return $this->getLocalFileForDistUrl($this->filename($dist));
    }

    /**
     * @return Option<string>
     */
    public function getLocalFileForDistUrl(string $distFilename): Option
    {
        $tmpLocalFilename = $this->getTempFileName();
        $tmpLocalFileHandle = \fopen(
            $tmpLocalFilename,
            'wb'
        );
        if (false === $tmpLocalFileHandle) {
            throw new \RuntimeException('Could not open temporary file for writing zip file for dist.');
        }

        $distReadStream = $this->readStream($distFilename)->getOrNull();
        if (null === $distReadStream) {
            return Option::none();
        }
        \stream_copy_to_stream($distReadStream, $tmpLocalFileHandle);
        \fclose($tmpLocalFileHandle);

        return Option::of($tmpLocalFilename);
    }

    private function getTempFileName(): string
    {
        return \sys_get_temp_dir().\DIRECTORY_SEPARATOR.\uniqid('repman-dist-', true);
    }

    /**
     * @return Option<resource>
     */
    private function readStream(string $path): Option
    {
        try {
            $resource = $this->repoFilesystem->readStream($path);
            if (false === $resource) {
                return Option::none();
            }
        } catch (FileNotFoundException $e) {
            return Option::none();
        }

        return Option::of($resource);
    }
}
