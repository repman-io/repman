<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Cache;

use Buddy\Repman\Service\AtomicFile;
use Buddy\Repman\Service\Cache;
use Buddy\Repman\Service\ExceptionHandler;
use Munus\Control\Option;
use Munus\Control\TryTo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class FileCache implements Cache
{
    private string $basePath;
    private ExceptionHandler $exceptionHandler;

    public function __construct(string $basePath, ExceptionHandler $exceptionHandler)
    {
        if (!is_dir($basePath)) {
            @mkdir($basePath, 0777, true);
        }
        $basePath = rtrim($basePath, '/');
        if (!is_writable($basePath)) {
            throw new \InvalidArgumentException(sprintf('Cache path %s must be writable', $basePath));
        }
        $this->basePath = $basePath;
        $this->exceptionHandler = $exceptionHandler;
    }

    public function get(string $path, callable $supplier, int $expireTime = 0): Option
    {
        $filename = $this->getPath($path);
        if (is_readable($filename) && ($expireTime === 0 || filemtime($filename) > time() - $expireTime)) {
            return Option::some(unserialize((string) file_get_contents($filename)));
        }

        $this->ensureDirExist($filename);

        return TryTo::run($supplier)
            ->onSuccess(function ($value) use ($filename): void {AtomicFile::write($filename, serialize($value)); })
            ->onFailure(function (\Throwable $throwable): void {$this->exceptionHandler->handle($throwable); })
            ->map(fn ($value) => Option::some($value))
            ->getOrElse(Option::none());
    }

    public function removeOld(string $path): void
    {
        $dir = $this->getPath(dirname($path));
        if (false === ($length = strpos(basename($path), '$')) || !is_dir($dir)) {
            return;
        }

        $pattern = substr(basename($path), 0, $length).'$*';
        $files = Finder::create()->files()->ignoreVCS(true)->name($pattern)->in($dir);

        foreach ($files as $file) {
            /* @var SplFileInfo $file */
            @unlink($file->getPathname());
        }
    }

    public function find(string $path): Option
    {
        $dir = $this->getPath(dirname($path));
        if (!is_dir($dir)) {
            return Option::none();
        }

        $pattern = basename($path).'$*';
        /** @var SplFileInfo[] $files */
        $files = iterator_to_array(Finder::create()->files()->ignoreVCS(true)->ignoreUnreadableDirs(true)->name($pattern)->in($dir)->getIterator());
        if ($files === []) {
            return Option::none();
        }

        return Option::some(unserialize((string) file_get_contents(current($files)->getPathname())));
    }

    public function exists(string $path, int $expireTime = 0): bool
    {
        $filename = $this->getPath($path);

        return is_readable($filename) && ($expireTime === 0 || filemtime($filename) > time() - $expireTime);
    }

    private function getPath(string $path): string
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
