<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Dist\Storage;

use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Downloader;
use League\Flysystem\FilesystemInterface;
use Munus\Control\Option;
use RuntimeException;
use function sprintf;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class StorageImpl implements Storage
{
    private Downloader $downloader;
    private FilesystemInterface $repoStorage;

    public function __construct(Downloader $downloader, FilesystemInterface $repoStorage)
    {
        $this->downloader = $downloader;
        $this->repoStorage = $repoStorage;
    }

    public function has(Dist $dist): bool
    {
        return $this->repoStorage->has($this->filename($dist));
    }

    /**
     * {@inheritdoc}
     */
    public function download(string $url, Dist $dist, array $headers = []): void
    {
        if ($this->has($dist)) {
            return;
        }

        $filename = $this->filename($dist);

        $this->repoStorage->writeStream(
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

    public function remove(Dist $dist): void
    {
        $filename = $this->filename($dist);
        if ($this->repoStorage->has($filename)) {
            $this->repoStorage->delete($filename);
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

    public function size(Dist $dist): int
    {
        $filename = $this->filename($dist);
        if ($this->repoStorage->has($filename)) {
            /** @phpstan-ignore-next-line - will always return int because file exists */
            return $this->repoStorage->getSize($filename);
        }

        return 0;
    }

    /**
     * @return Option<resource>
     */
    public function readDistStream(Dist $dist): Option
    {
        $resource = $this->repoStorage->readStream($this->filename($dist));
        if (false === $resource) {
            return Option::none();
        }

        return Option::of($resource);
    }
}
