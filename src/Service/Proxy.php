<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Composer\Semver\VersionParser;
use Munus\Collection\GenericList;
use Munus\Control\Option;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class Proxy
{
    public const PACKAGES_PATH = 'packages.json';
    public const PACKAGES_EXPIRE_TIME = 60;

    private string $url;
    private string $name;
    private Downloader $downloader;
    private Cache $cache;
    private string $distsDir;

    public function __construct(string $name, string $url, Downloader $downloader, Cache $cache, string $distsDir)
    {
        $this->name = $name;
        $this->url = rtrim($url, '/');
        $this->downloader = $downloader;
        $this->cache = $cache;
        $this->distsDir = rtrim($distsDir, '/');
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Option<string>
     */
    public function distFilename(string $package, string $version, string $ref, string $format): Option
    {
        $filename = $this->getCachePath(sprintf('dist/%s/%s_%s.%s', $package, $version, $ref, $format));
        if (!$this->cache->exists($filename)) {
            $providerData = $this->providerData($package)->getOrElse([]);
            if (!isset($providerData['packages'][$package])) {
                return Option::none();
            }
            $parser = new VersionParser();
            foreach ($providerData['packages'][$package] as $packageVersion) {
                if (!isset($packageVersion['version_normalized'])) {
                    $packageVersion['version_normalized'] = $parser->normalize($packageVersion['version']);
                }

                if ($packageVersion['version_normalized'] === $version && isset($packageVersion['dist']['url'])) {
                    $this->cache->put($filename, $this->downloader->getContents($packageVersion['dist']['url'])->getOrElseThrow(
                        new \RuntimeException(sprintf('Failed to download %s from %s', $package, $packageVersion['dist']['url']))
                    ));
                }
            }
        }
        $distFilename = $this->distsDir.'/'.$filename;

        return Option::when(file_exists($distFilename), $distFilename);
    }

    /**
     * @return Option<array<mixed>>
     */
    public function providerData(string $package): Option
    {
        $providerPath = $this->getProviderPath($package);
        if ($providerPath->isEmpty()) {
            return Option::none();
        }

        $contents = $this->cache->get($this->getCachePath($providerPath->get()), fn () => $this->downloader->getContents($this->url.'/'.$providerPath->get())->getOrElse(''));
        if ($contents->isEmpty()) {
            return Option::none();
        }

        return Option::some(Json::decode($contents->get()));
    }

    /**
     * @return GenericList<string>
     */
    public function syncedPackages(): GenericList
    {
        $dir = $this->distsDir.'/'.$this->getCachePath('p');
        if (!is_dir($dir)) {
            return GenericList::empty();
        }

        $files = Finder::create()->files()->ignoreVCS(true)
            ->name('/\.json$/')->notName('/^provider-/')
            ->in($this->distsDir.'/'.$this->getCachePath('p'));
        $packages = [];
        foreach ($files as $file) {
            /* @var SplFileInfo $file */
            if (false === $length = strpos($file->getRelativePathname(), '$')) {
                continue;
            }
            $packages[] = substr($file->getRelativePathname(), 0, $length);
        }

        return GenericList::ofAll($packages);
    }

    /**
     * @return Option<string>
     */
    private function getProviderPath(string $packageName): Option
    {
        $root = $this->getRootPackages();
        if (isset($root['provider-includes'])) {
            foreach ($root['provider-includes'] as $url => $meta) {
                $filename = str_replace('%hash%', $meta['sha256'], $url);
                $contents = $this->cache->get($this->getCachePath($filename), fn () => $this->downloader->getContents($this->url.'/'.$filename)->getOrElse(''));
                $data = Json::decode($contents->getOrElse('{}'));
                if (isset($data['providers'][$packageName])) {
                    return Option::some(
                        (string) str_replace(
                            ['%package%', '%hash%'],
                            [$packageName, $data['providers'][$packageName]['sha256']],
                            $root['providers-url']
                        )
                    );
                }
            }
        }

        return Option::none();
    }

    /**
     * @return array<mixed>
     */
    private function getRootPackages(): array
    {
        $contents = $this->cache->get($this->getCachePath(self::PACKAGES_PATH), function (): string {
            return $this->downloader->getContents($this->getUrl(self::PACKAGES_PATH))->getOrElse('');
        }, self::PACKAGES_EXPIRE_TIME);

        return Json::decode($contents->getOrElse('{}'));
    }

    private function getUrl(string $path): string
    {
        return sprintf('%s/%s', $this->url, $path);
    }

    private function getCachePath(string $path): string
    {
        return sprintf('%s/%s', $this->name, $path);
    }
}
