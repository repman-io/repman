<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Dist;

use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Downloader;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Munus\Control\Option;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use function fclose;
use function fopen;
use function sprintf;
use function stream_copy_to_stream;
use function sys_get_temp_dir;
use function uniqid;
use const DIRECTORY_SEPARATOR;

class Storage
{
    public function __construct(private readonly Downloader $downloader, private readonly FilesystemOperator $repoFilesystem)
    {
    }

    public function has(Dist $dist): bool
    {
        return $this->repoFilesystem->fileExists($this->filename($dist));
    }

    /**
     * @param string[] $headers
     *
     * @throws FilesystemException|Throwable
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
                    throw new NotFoundHttpException(sprintf('File not found at %s', $url));
                }
            )->getOrElseThrow(
                new RuntimeException(sprintf('Failed to download %s from %s', $dist->package(), $url))
            )
        );
    }

    /**
     * @throws FilesystemException
     */
    public function remove(Dist $dist): void
    {
        $filename = $this->filename($dist);
        if ($this->repoFilesystem->fileExists($filename)) {
            $this->repoFilesystem->delete($filename);
        }
    }

    public function filename(Dist $dist): string
    {
        return sprintf(
            '%s/dist/%s/%s_%s.%s',
            $dist->repo(),
            $dist->package(),
            $dist->version(),
            $dist->ref(),
            $dist->format()
        );
    }

    /**
     * @throws FilesystemException
     */
    public function size(Dist $dist): int
    {
        $filename = $this->filename($dist);
        if ($this->repoFilesystem->fileExists($filename)) {
            /* @phpstan-ignore-next-line - will always return int because file exists */
            return $this->repoFilesystem->fileSize($filename);
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
        $tmpLocalFileHandle = fopen(
            $tmpLocalFilename,
            'wb'
        );
        if (false === $tmpLocalFileHandle) {
            throw new RuntimeException('Could not open temporary file for writing zip file for dist.');
        }

        $distReadStream = $this->readStream($distFilename)->getOrNull();
        if (null === $distReadStream) {
            return Option::none();
        }

        stream_copy_to_stream($distReadStream, $tmpLocalFileHandle);
        fclose($tmpLocalFileHandle);

        return Option::of($tmpLocalFilename);
    }

    private function getTempFileName(): string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('repman-dist-', true);
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
        } catch (UnableToReadFile|FilesystemException) {
            return Option::none();
        }

        return Option::of($resource);
    }
}
