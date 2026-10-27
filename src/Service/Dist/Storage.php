<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Dist;

use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Downloader;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Munus\Control\Option;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Storage
{
    private Downloader $downloader;
    private FilesystemOperator $repoFilesystem;

    public function __construct(Downloader $downloader, FilesystemOperator $repoFilesystem)
    {
        $this->downloader = $downloader;
        $this->repoFilesystem = $repoFilesystem;
    }

    public function has(Dist $dist): bool
    {
        return $this->repoFilesystem->fileExists($this->filename($dist));
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
        $this->repoFilesystem->delete($this->filename($dist));
    }

    public function filename(Dist $dist): string
    {
        return \sprintf(
            '%s/dist/%s/%s_%s.%s',
            $dist->repo(),
            $dist->package(),
            $dist->version(),
            $dist->ref(),
            $dist->format()
        );
    }

    public function size(Dist $dist): int
    {
        try {
            return $this->repoFilesystem->fileSize($this->filename($dist));
        } catch (FilesystemException $exception) {
            return 0;
        }
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
        $distReadStream = $this->readStream($distFilename)->getOrNull();
        if (null === $distReadStream) {
            return Option::none();
        }

        $tmpLocalFilename = $this->getTempFileName();
        $tmpLocalFileHandle = \fopen(
            $tmpLocalFilename,
            'wb'
        );
        if (false === $tmpLocalFileHandle) {
            throw new \RuntimeException('Could not open temporary file for writing zip file for dist.');
        }

        \stream_copy_to_stream($distReadStream, $tmpLocalFileHandle);
        \fclose($tmpLocalFileHandle);
        \fclose($distReadStream);

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
            return Option::of($this->repoFilesystem->readStream($path));
        } catch (FilesystemException $e) {
            return Option::none();
        }
    }
}
