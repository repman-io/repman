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
    private FilesystemInterface $filesystem;
    private Downloader $downloader;

    public function __construct(FilesystemInterface $filesystem, Downloader $downloader)
    {
        $this->filesystem = $filesystem;
        $this->downloader = $downloader;
    }

    public function has(Dist $dist): bool
    {
        return $this->filesystem->has($this->filename($dist));
    }

    /**
     * @param string[] $headers
     */
    public function download(string $url, Dist $dist, array $headers = []): void
    {
        if ($this->has($dist)) {
            return;
        }

        $contents = $this->downloader->getContents($url, $headers, function () use ($url): void {
            throw new NotFoundHttpException(sprintf('File not found at %s', $url));
        })->getOrElseThrow(
            new \RuntimeException(sprintf('Failed to download %s from %s', $dist->package(), $url))
        );

        $filename = $this->filename($dist);
        $this->filesystem->put($filename, $contents);
    }

    /**
     * @return Option<resource>
     */
    public function getStream(Dist $dist): Option
    {
        try {
            $stream = $this->filesystem->readStream($this->filename($dist));

            return Option::some($stream);
        } catch (FileNotFoundException $e) {
            return Option::none();
        }
    }

    private function filename(Dist $dist): string
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
}
