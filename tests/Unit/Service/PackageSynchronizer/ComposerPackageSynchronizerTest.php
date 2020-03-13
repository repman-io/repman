<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\PackageSynchronizer;

use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Dist\Storage\FileStorage;
use Buddy\Repman\Service\Dist\Storage\InMemoryStorage;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\PackageNormalizer;
use Buddy\Repman\Service\PackageSynchronizer\ComposerPackageSynchronizer;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use Buddy\Repman\Tests\MotherObject\PackageMother;
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
        $this->synchronizer = new ComposerPackageSynchronizer(
            new PackageManager(new FileStorage($this->baseDir, new FakeDownloader()), $this->baseDir),
            new PackageNormalizer(),
            $this->repoMock = $this->createMock(PackageRepository::class),
            new InMemoryStorage()
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
        $this->synchronizer->synchronize(PackageMother::withOrganization('artifact', '/non/exist/path', 'buddy'));
        // exception was not throw
        self::assertTrue(true);
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

    public function testSynchronizePackageWithToken(): void
    {
        $path = $this->baseDir.'/buddy/p/repman-io/repman.json';
        @unlink($path);

        $this->synchronizer->synchronize(PackageMother::withOrganizationAndToken('gitlab-oauth', $this->resourcesDir.'artifacts', 'buddy'));

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertTrue($json['packages']['repman-io/repman'] !== []);
        @unlink($path);
    }

    public function testSynchronizePackageWithInvalidName(): void
    {
        $this->synchronizer->synchronize(PackageMother::withOrganization('path', $this->resourcesDir.'path/invalid-name', 'buddy'));
        // exception was not thrown
        self::assertTrue(true);
    }

    public function testSynchronizePackageWithInvalidPath(): void
    {
        $this->synchronizer->synchronize(PackageMother::withOrganization('path', $this->resourcesDir, 'buddy'));
        // exception was not thrown
        self::assertTrue(true);
    }
}
