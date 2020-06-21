<?php declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use League\Flysystem\FilesystemInterface;
use Munus\Collection\GenericList;

class PackageManager
{
    private FilesystemInterface $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return GenericList<string>
     */
    public function packages(string $repo): GenericList
    {
        $packages = [];
        $vendors = $this->filesystem->listContents("$repo/dist");

        foreach ($vendors as $vendor) {
            $vendorPackages = $this->filesystem->listContents("$repo/dist/{$vendor['basename']}");
            foreach ($vendorPackages as $vendorPackage) {
                $packages[] = str_replace("$repo/dist/", '', $vendorPackage['path']);
            }
        }

        return GenericList::ofAll($packages);
    }

    public function remove(string $repo, string $package): void
    {
        $this->filesystem->deleteDir("$repo/dist/$package");

        $vendor = strstr($package, '/', true);
        $vendorPackages = $this->filesystem->listContents("$repo/dist/$vendor");
        if (empty($vendorPackages)) {
            $this->filesystem->deleteDir("$repo/dist/$vendor");
        }
    }
}
