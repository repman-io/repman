<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;

final class PackageManagerTest extends TestCase
{
    private string $baseDir;
    private PackageManager $packageManager;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir().'/repman';
        $this->packageManager = $this->createPackageManager($this->baseDir);
    }

    public function testFindProvidersForPackage(): void
    {
        $packageManager = $this->createPackageManager(__DIR__ . '/../../../Resources/fixtures');

        $providers = $packageManager->findProviders('buddy', [
            new PackageName('id', 'buddy-works/repman'),
            new PackageName('id', 'not-exist/missing'),
        ]);

        self::assertEquals(['buddy-works/repman' => ['1.2.3' => [
            'version' => '1.2.3',
            'dist' => [
                'type' => 'zip',
                'url' => '/path/to/reference.zip',
                'reference' => 'ac7dcaf888af2324cd14200769362129c8dd8550',
            ],
            'version_normalized' => '1.2.3.0',
        ]]], $providers);
    }

    public function testRemoveProvider(): void
    {
        $org = 'buddy';
        $package1 = 'vendor/package1';
        $package2 = 'vendor/package2';

        $this->packageManager->saveProvider([], $org, $package1);
        $this->packageManager->saveProvider([], $org, $package2);

        self::assertTrue(file_exists($this->baseDir.'/buddy/p/'.$package1.'.json'));
        self::assertTrue(file_exists($this->baseDir.'/buddy/p/'.$package2.'.json'));

        $this->packageManager->removeProvider($org, $package1);

        self::assertTrue(is_dir($this->baseDir.'/buddy'));
        self::assertTrue(is_dir(dirname($this->baseDir.'/buddy/p/'.$package1)));
        self::assertFalse(file_exists($this->baseDir.'/buddy/p/'.$package1.'.json'));
        self::assertTrue(file_exists($this->baseDir.'/buddy/p/'.$package2.'.json'));
    }

    public function testRemoveDist(): void
    {
        $org = 'buddy';
        $package1 = 'vendor/package1';
        $package2 = 'vendor/package2';

        @mkdir($this->baseDir.'/buddy/dist/'.$package1, 0777, true);
        @mkdir($this->baseDir.'/buddy/dist/'.$package2, 0777, true);

        $this->packageManager->removeDist($org, $package1);

        self::assertTrue(is_dir($this->baseDir.'/buddy'));
        self::assertTrue(is_dir($this->baseDir.'/buddy/dist/vendor'));
        self::assertFalse(is_dir($this->baseDir.'/buddy/dist/'.$package1));
        self::assertTrue(is_dir($this->baseDir.'/buddy/dist/'.$package2));
    }

    public function testRemoveOrganizationDir(): void
    {
        $org = 'buddy';
        $package = 'hello/world';

        $this->packageManager->saveProvider([], $org, $package);

        self::assertTrue(is_dir($this->baseDir.'/buddy/p/hello'));

        $this->packageManager->removeProvider($org, $package)
            ->removeOrganizationDir($org);

        self::assertFalse(is_dir($this->baseDir.'/buddy'));
    }

    private function createPackageManager(string $baseDirectory): PackageManager
    {
        $localAdapter = new Local($baseDirectory);
        $filesystem = new Filesystem($localAdapter);

        $distStorage = new Dist\Storage($filesystem, new FakeDownloader());
        return new PackageManager($filesystem, $distStorage);
    }
}
