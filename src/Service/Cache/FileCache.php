<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Cache;

use Buddy\Repman\Service\Cache;
use Munus\Control\Option;
use Munus\Control\TryTo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class FileCache implements Cache
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }
        $basePath = rtrim($basePath, '/');
        if (!is_writable($basePath)) {
            throw new \InvalidArgumentException(sprintf('Cache path %s must be writable', $basePath));
        }
        $this->basePath = $basePath;
    }

    public function get(string $path, callable $supplier, int $expireTime = 0): Option
    {
        $filename = $this->getFilename($path);
        if (is_readable($filename) && ($expireTime === 0 || filemtime($filename) > time() - $expireTime)) {
            return Option::some(unserialize((string) file_get_contents($filename)));
        }

        $this->ensureDirExist($filename);

        return TryTo::run($supplier)
            ->onSuccess(fn ($value) => file_put_contents($filename, serialize($value)))
            ->map(fn ($value) => Option::some($value))
            ->getOrElse(Option::none());
    }

    public function removeOld(string $path): void
    {
        if (false === $length = strpos(basename($path), '$')) {
            return;
        }

        $pattern = substr(basename($path), 0, $length).'$*';
        $files = Finder::create()->files()->ignoreVCS(true)->name($pattern)->in($this->basePath.'/'.dirname($path));

        foreach ($files as $file) {
            /* @var SplFileInfo $file */
            @unlink($file->getPathname());
        }
    }

    private function getFilename(string $path): string
    {
        return sprintf('%s/%s', $this->basePath, $path);
    }

    private function ensureDirExist(string $filename): void
    {
        $dirname = dirname($filename);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
    }
}
