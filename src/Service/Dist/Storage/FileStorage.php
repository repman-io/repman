<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Dist\Storage;

use Buddy\Repman\Service\AtomicFile;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Downloader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class FileStorage implements Storage
{
    private string $distsDir;
    private Downloader $downloader;

    public function __construct(string $distsDir, Downloader $downloader)
    {
        $this->distsDir = $distsDir;
        $this->downloader = $downloader;
    }

    public function has(Dist $dist): bool
    {
        return is_readable($this->filename($dist));
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
        $this->ensureDirExist($filename);

        AtomicFile::write(
            $filename,
            (string) stream_get_contents($this->downloader->getContents($url, $headers, function () use ($url): void {
                throw new NotFoundHttpException(sprintf('File not found at %s', $url));
            })->getOrElseThrow(
                new \RuntimeException(sprintf('Failed to download %s from %s', $dist->package(), $url))
            ))
        );
    }

    public function filename(Dist $dist): string
    {
        return sprintf(
            '%s/%s/dist/%s/%s_%s.%s',
            $this->distsDir,
            $dist->repo(),
            $dist->package(),
            $dist->version(),
            $dist->ref(),
            $dist->format()
        );
    }

    public function size(Dist $dist): int
    {
        $size = filesize($this->filename($dist));

        return $size === false ? 0 : $size;
    }

    private function ensureDirExist(string $filename): void
    {
        $dirname = dirname($filename);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
    }
}
