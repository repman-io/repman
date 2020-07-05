<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Cache;

use Buddy\Repman\Service\Cache;
use Buddy\Repman\Service\ExceptionHandler;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Munus\Control\Option;
use Munus\Control\TryTo;

final class FileCache implements Cache
{
    private FilesystemInterface $proxyStorage;
    private ExceptionHandler $exceptionHandler;

    public function __construct(FilesystemInterface $proxyStorage, ExceptionHandler $exceptionHandler)
    {
        $this->proxyStorage = $proxyStorage;
        $this->exceptionHandler = $exceptionHandler;
    }

    public function get(string $path, callable $supplier, int $expireTime = 0): Option
    {
        if ($this->exists($path, $expireTime)) {
            $contents = $this->proxyStorage->read($path);

            return $contents === false
                ? Option::none()
                : Option::some(unserialize($contents, ['allowed_classes' => false]));
        }

        return TryTo::run($supplier)
            ->onSuccess(fn ($value) => $this->proxyStorage->put($path, serialize($value)))
            ->onFailure(function (\Throwable $throwable): void { $this->exceptionHandler->handle($throwable); })
            ->map(fn ($value) => Option::some($value))
            ->getOrElse(Option::none());
    }

    public function removeOld(string $path): void
    {
        $package = strstr($path, '$', true);
        if ($package !== false) {
            foreach ($this->findMatchingFiles($package) as $file) {
                $this->proxyStorage->delete($file['path']);
            }
        }
    }

    public function find(string $path, int $expireTime = 0): Option
    {
        foreach ($this->findMatchingFiles($path) as $file) {
            if ($expireTime === 0 || $file['timestamp'] > time() - $expireTime) {
                $contents = (string) $this->proxyStorage->read($file['path']);

                return Option::some(unserialize($contents, ['allowed_classes' => false]));
            }
        }

        return Option::none();
    }

    public function exists(string $path, int $expireTime = 0): bool
    {
        try {
            return $this->proxyStorage->has($path) && ($expireTime === 0 || $this->proxyStorage->getTimestamp($path) > time() - $expireTime);
        } catch (FileNotFoundException $e) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function findMatchingFiles(string $path): array
    {
        $dir = dirname($path);
        if (!$this->proxyStorage->has($dir)) {
            return [];
        }

        $package = basename($path);

        return array_filter(
            $this->proxyStorage->listContents($dir),
            fn (array $file) => strpos($file['basename'], "$package$") === 0
        );
    }
}
