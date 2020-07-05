<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use League\Flysystem\FilesystemInterface;
use Munus\Collection\GenericList;

class PackageManager
{
    private FilesystemInterface $proxyStorage;

    public function __construct(FilesystemInterface $proxyStorage)
    {
        $this->proxyStorage = $proxyStorage;
    }

    /**
     * @return GenericList<string>
     */
    public function packages(string $repo): GenericList
    {
        $packages = [];
        $vendors = $this->proxyStorage->listContents("$repo/dist");

        foreach ($vendors as $vendor) {
            $vendorPackages = $this->proxyStorage->listContents("$repo/dist/{$vendor['basename']}");
            foreach ($vendorPackages as $vendorPackage) {
                $packages[] = str_replace("$repo/dist/", '', $vendorPackage['path']);
            }
        }

        return $packages !== [] ? GenericList::ofAll($packages) : GenericList::empty();
    }

    public function remove(string $repo, string $package): void
    {
        $this->proxyStorage->deleteDir("$repo/dist/$package");

        $vendor = strstr($package, '/', true);
        $vendorPackages = $this->proxyStorage->listContents("$repo/dist/$vendor");
        if ($vendorPackages === []) {
            $this->proxyStorage->deleteDir("$repo/dist/$vendor");
        }
    }
}
