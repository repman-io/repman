<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Munus\Control\Option;

final class Proxy
{
    private string $baseUrl;
    private RemoteFilesystem $remoteFilesystem;

    public function __construct(string $baseUrl, RemoteFilesystem $remoteFilesystem)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->remoteFilesystem = $remoteFilesystem;
    }

    /**
     * @return Option<array<mixed>>
     */
    public function provider(string $packageName): Option
    {
        $providerPath = $this->getProviderPath($packageName);
        if ($providerPath->isEmpty()) {
            return Option::none();
        }

        $contents = $this->remoteFilesystem->getContents($this->baseUrl.'/'.$providerPath->get());
        if ($contents->isEmpty()) {
            return Option::none();
        }

        return Option::some(Json::decode($contents->get()));
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
                $contents = $this->remoteFilesystem->getContents($this->baseUrl.'/'.$filename);
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
        $rootPackages = $this->baseUrl.'/packages.json';
        $contents = $this->remoteFilesystem->getContents($rootPackages);

        return Json::decode($contents->getOrElse('{}'));
    }
}
