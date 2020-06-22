<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\PackageSynchronizer;

use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Downloader\NativeDownloader;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\PackageNormalizer;
use Buddy\Repman\Service\PackageSynchronizer\ComposerPackageSynchronizer;
use Buddy\Repman\Tests\MotherObject\PackageMother;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ComposerPackageSynchronizerTest extends TestCase
{
    private ComposerPackageSynchronizer $synchronizer;
    /** @var PackageRepository|MockObject */
    private $repoMock;
    private string $baseDir;
    private string $resourcesDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir().'/repman';

        $localAdapter = new Local($this->baseDir);
        $filesystem = new Filesystem($localAdapter);

        $distStorage = new Storage($filesystem, new NativeDownloader());
        $packageManager = new PackageManager($filesystem, $distStorage);

        $this->synchronizer = new ComposerPackageSynchronizer(
            $packageManager,
            new PackageNormalizer(),
            $this->repoMock = $this->createMock(PackageRepository::class),
            $distStorage,
            'gitlab.com'
        );
        $this->resourcesDir = __DIR__.'/../../../Resources/';
    }

    public function testSynchronizePackageFromLocalPath(): void
    {
        $path = $this->baseDir.'/buddy/p/repman-io/repman.json';
        @unlink($path);

        $this->synchronizer->synchronize(PackageMother::withOrganization('path', __DIR__.'/../../../../', 'buddy'));

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertTrue($json['packages']['repman-io/repman'] !== []);
        @unlink($path);
    }

    public function testSynchronizeError(): void
    {
        $this->synchronizer->synchronize($package = PackageMother::withOrganization('artifact', '/non/exist/path', 'buddy'));

        self::assertEquals('Error: RecursiveDirectoryIterator::__construct(/non/exist/path): failed to open dir: No such file or directory', $this->getProperty($package, 'lastSyncError'));
    }

    public function testSynchronizePackageFromArtifacts(): void
    {
        $path = $this->baseDir.'/buddy/p/buddy-works/alpha.json';
        @unlink($path);

        $this->synchronizer->synchronize(PackageMother::withOrganization('artifact', $this->resourcesDir.'artifacts', 'buddy'));

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertCount(4, $json['packages']['buddy-works/alpha']);
        @unlink($path);
    }

    public function testSynchronizePackageThatAlreadyExists(): void
    {
        $path = $this->baseDir.'/buddy/p/buddy-works/alpha.json';
        @unlink($path);
        $this->repoMock->method('packageExist')->willReturn(true);

        $this->synchronizer->synchronize(PackageMother::withOrganization('artifact', $this->resourcesDir.'artifacts', 'buddy'));

        self::assertFileNotExists($path);
    }

    public function testSynchronizePackageWithGitLabToken(): void
    {
        $path = $this->baseDir.'/buddy/p/repman-io/repman.json';
        @unlink($path);

        $this->synchronizer->synchronize(PackageMother::withOrganizationAndToken('gitlab', $this->resourcesDir.'artifacts', 'buddy'));

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertTrue($json['packages']['repman-io/repman'] !== []);
        @unlink($path);
    }

    public function testSynchronizePackageWithGitHubToken(): void
    {
        $path = $this->baseDir.'/buddy/p/repman-io/repman.json';
        @unlink($path);

        $this->synchronizer->synchronize(PackageMother::withOrganizationAndToken('github', $this->resourcesDir.'artifacts', 'buddy'));

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertTrue($json['packages']['repman-io/repman'] !== []);
        @unlink($path);
    }

    public function testSynchronizePackageWithBitbucketToken(): void
    {
        $path = $this->baseDir.'/buddy/p/repman-io/repman.json';
        @unlink($path);

        $this->synchronizer->synchronize(PackageMother::withOrganizationAndToken('bitbucket', $this->resourcesDir.'artifacts', 'buddy'));

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertTrue($json['packages']['repman-io/repman'] !== []);
        @unlink($path);
    }

    public function testSynchronizePackageWithInvalidName(): void
    {
        $package = PackageMother::withOrganization('path', $this->resourcesDir.'path/invalid-name', 'buddy');

        $this->synchronizer->synchronize($package);

        self::assertEquals('Error: Package name ../other/package is invalid', $this->getProperty($package, 'lastSyncError'));
    }

    public function testSynchronizePackageWithInvalidPath(): void
    {
        $package = PackageMother::withOrganization('path', $this->resourcesDir, 'buddy');

        $this->synchronizer->synchronize($package);

        self::assertEquals('Error: Package not found', $this->getProperty($package, 'lastSyncError'));
    }

    public function testSynchronizePackageWithNoStableRelease(): void
    {
        // prepare package in path without git
        $resPath = $this->resourcesDir.'path/unstable/composer.json';
        $tmpPath = sys_get_temp_dir().'/repman/path/unstable/composer.json';
        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0777, true);
        }
        copy($resPath, $tmpPath);

        $package = PackageMother::withOrganization('path', dirname($tmpPath), 'buddy');

        $this->synchronizer->synchronize($package);

        self::assertEquals('no stable release', $this->getProperty($package, 'latestReleasedVersion'));
        @unlink($this->baseDir.'/buddy/p/some/package.json');
        @unlink($tmpPath);
    }

    /**
     * @return mixed
     */
    private function getProperty(object $object, string $property)
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
