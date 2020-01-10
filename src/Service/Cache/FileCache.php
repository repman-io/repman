<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Cache;

use Buddy\Repman\Service\Cache;
use Munus\Control\Option;
use Munus\Control\TryTo;

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

    public function get(string $path, callable $supplier): Option
    {
        $filename = $this->getFilename($path);
        if (is_readable($filename)) {
            return Option::some((string) file_get_contents($filename));
        }

        $this->ensureDirExist($filename);

        return TryTo::run($supplier)
            ->onSuccess(fn ($value) => file_put_contents($filename, $value))
            ->map(fn ($value) => Option::some($value))
            ->getOrElse(Option::none());
    }

    public function put(string $path, string $contents): void
    {
        $filename = $this->getFilename($path);
        $this->ensureDirExist($filename);
        file_put_contents($filename, $contents);
    }

    public function exists(string $path): bool
    {
        return file_exists($filename = $this->getFilename($path));
    }

    public function delete(string $path): void
    {
        $filename = $this->getFilename($path);
        if (file_exists($filename)) {
            @unlink($filename);
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
