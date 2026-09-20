<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\PackageSynchronizer;

use Buddy\Repman\Entity\Organization\Package\Link;
use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\PackageNormalizer;
use Buddy\Repman\Service\PackageSynchronizer\ComposerPackageSynchronizer;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
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
    private FakeDownloader $downloader;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir().'/repman';
        $repoFilesystem = new Filesystem(new Local($this->baseDir));
        $this->downloader = new FakeDownloader();
        $distStorage = new Storage($this->downloader, $repoFilesystem);
        $this->synchronizer = new ComposerPackageSynchronizer(
            new PackageManager($distStorage, $repoFilesystem),
            new PackageNormalizer(),
            $this->repoMock = $this->createMock(PackageRepository::class),
            $distStorage,
            $this->createMock(UserOAuthTokenRefresher::class),
            'gitlab.com'
        );
        $this->resourcesDir = dirname(__DIR__, 3).'/Resources/';
    }

    public function testSynchronizePackageFromLocalPath(): void
    {
        $path = $this->baseDir.'/buddy/p/repman-io/repman.json';
        @unlink($path);

        $basePath = dirname(__DIR__, 4);
        $this->downloader->addContent($basePath, 'foobar');

        $package = PackageMother::withOrganization('path', $basePath, 'buddy');
        $this->synchronizer->synchronize($package);

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertTrue($json['packages']['repman-io/repman'] !== []);
        @unlink($path);

        self::assertCount(1, $package->versions());
    }

    public function testSynchronizeError(): void
    {
        $this->synchronizer->synchronize($package = PackageMother::withOrganization('artifact', '/non/exist/path', 'buddy'));

        self::assertMatchesRegularExpression('/Error: RecursiveDirectoryIterator::__construct\(\/non\/exist\/path\): (F|f)ailed to open (dir|directory): No such file or directory/', $this->getProperty($package, 'lastSyncError'));
    }

    public function testSynchronizePackageFromArtifacts(): void
    {
        $path = $this->baseDir.'/buddy/p/buddy-works/alpha.json';
        @unlink($path);

        $this->downloader->addContent($this->resourcesDir.'artifacts', 'foobar');
        $package = PackageMother::withOrganization('artifact', $this->resourcesDir.'artifacts', 'buddy');
        $this->synchronizer->synchronize($package);

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertCount(4, $json['packages']['buddy-works/alpha']);
        @unlink($path);

        self::assertCount(4, $package->versions());
        $versionStrings = array_map(function (Version $version): string {
            return $version->version();
        }, $package->versions()->toArray());
        sort($versionStrings, SORT_NATURAL);
        self::assertEquals(['1.0.0', '1.1.0', '1.1.1', '1.2.0'], $versionStrings);

        /** @var Link[] $links */
        $links = $package->links()->toArray();
        self::assertCount(6, $links);

        $linkStrings = array_map(
            fn (Link $link): string => $link->type().'-'.$link->target().'-'.$link->constraint(),
            $links
        );

        self::assertContains('requires-php-^7.4.1', $linkStrings);
        self::assertContains('devRequires-buddy-works/dev-^1.0', $linkStrings);
        self::assertContains('provides-buddy-works/provide-^1.0', $linkStrings);
        self::assertContains('replaces-buddy-works/replace-^1.0', $linkStrings);
        self::assertContains('conflicts-buddy-works/conflict-^1.0', $linkStrings);
        self::assertContains('suggests-buddy-works/suggests-You really should', $linkStrings);

        $referencestrings = array_map(function (Version $version): string {
            return $version->reference();
        }, $package->versions()->toArray());
        self::assertEquals(['', '', '', ''], $referencestrings);
    }

    public function testWithMostRecentUnstable(): void
    {
        $this->downloader->addContent($this->resourcesDir.'artifacts-mixed-sorting', 'foobar');
        $package = PackageMother::withOrganization('artifact', $this->resourcesDir.'artifacts-mixed-sorting', 'buddy');
        $this->synchronizer->synchronize($package);

        self::assertEquals('v1.0.0', $this->getProperty($package, 'latestReleasedVersion'));
    }

    public function testSynchronizePackageThatAlreadyExists(): void
    {
        $path = $this->baseDir.'/buddy/p/buddy-works/alpha.json';
        @unlink($path);
        $this->repoMock->method('packageExist')->willReturn(true);

        $this->synchronizer->synchronize(PackageMother::withOrganization('artifact', $this->resourcesDir.'artifacts', 'buddy'));

        self::assertFileDoesNotExist($path);
    }

    public function testSynchronizePackageWithGitLabToken(): void
    {
        $path = $this->baseDir.'/buddy/p/repman-io/repman.json';
        @unlink($path);

        $package = PackageMother::withOrganizationAndToken('gitlab', $this->resourcesDir.'artifacts', 'buddy');
        $this->synchronizer->synchronize($package);

        self::assertTrue($package->isSynchronizedSuccessfully(), (string) $this->getProperty($package, 'lastSyncError'));
        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertTrue($json['packages']['repman-io/repman'] !== []);
        @unlink($path);
    }

    public function testSynchronizePackageWithGitHubToken(): void
    {
        $path = $this->baseDir.'/buddy/p/repman-io/repman.json';
        @unlink($path);

        $package = PackageMother::withOrganizationAndToken('github', $this->resourcesDir.'artifacts', 'buddy');
        $this->synchronizer->synchronize($package);

        self::assertTrue($package->isSynchronizedSuccessfully(), (string) $this->getProperty($package, 'lastSyncError'));
        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertTrue($json['packages']['repman-io/repman'] !== []);
        @unlink($path);
    }

    public function testSynchronizePackageWithBitbucketToken(): void
    {
        $path = $this->baseDir.'/buddy/p/repman-io/repman.json';
        @unlink($path);

        $package = PackageMother::withOrganizationAndToken('bitbucket', $this->resourcesDir.'artifacts', 'buddy');
        $this->synchronizer->synchronize($package);

        self::assertTrue($package->isSynchronizedSuccessfully(), (string) $this->getProperty($package, 'lastSyncError'));
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

        /* @phpstan-ignore-next-line */
        $this->downloader->addContent(dirname($tmpPath), file_get_contents($tmpPath));
        $this->synchronizer->synchronize($package);

        self::assertEquals('no stable release', $this->getProperty($package, 'latestReleasedVersion'));
        @unlink($this->baseDir.'/buddy/p/some/package.json');
        @unlink($tmpPath);
    }

    public function testSynchronizePackageWithLimitedNumberOfVersions(): void
    {
        $path = $this->baseDir.'/buddy/p/buddy-works/alpha.json';
        @unlink($path);

        $limit = 2;

        $package = PackageMother::withOrganization('artifact', $this->resourcesDir.'artifacts', 'buddy', $limit);
        $this->synchronizer->synchronize($package);

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertCount(4, $json['packages']['buddy-works/alpha']);
        @unlink($path);

        self::assertCount($limit, $package->versions());
        $versionStrings = array_map(function (Version $version): string {
            return $version->version();
        }, $package->versions()->toArray());
        sort($versionStrings, SORT_NATURAL);
        self::assertEquals(['1.1.1', '1.2.0'], $versionStrings);
    }

    public function testSynchronizePackageAbandonedWithReplacementPackage(): void
    {
        // prepare package in path without git
        $resPath = $this->resourcesDir.'path/abandoned-replacement-package/composer.json';
        $tmpPath = sys_get_temp_dir().'/repman/path/abandoned-replacement-package/composer.json';
        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0777, true);
        }
        copy($resPath, $tmpPath);

        $package = PackageMother::withOrganization('path', dirname($tmpPath), 'buddy');

        /* @phpstan-ignore-next-line */
        $this->downloader->addContent(dirname($tmpPath), file_get_contents($tmpPath));
        $this->synchronizer->synchronize($package);

        self::assertEquals('foo/bar', $this->getProperty($package, 'replacementPackage'));
        @unlink($this->baseDir.'/buddy/p/some/package.json');
        @unlink($tmpPath);
    }

    public function testSynchronizePackageAbandonedWithoutReplacementPackage(): void
    {
        // prepare package in path without git
        $resPath = $this->resourcesDir.'path/abandoned-without-replacement-package/composer.json';
        $tmpPath = sys_get_temp_dir().'/repman/path/abandoned-without-replacement-package/composer.json';
        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0777, true);
        }
        copy($resPath, $tmpPath);

        $package = PackageMother::withOrganization('path', dirname($tmpPath), 'buddy');

        /* @phpstan-ignore-next-line */
        $this->downloader->addContent(dirname($tmpPath), file_get_contents($tmpPath));
        $this->synchronizer->synchronize($package);

        self::assertEquals('', $this->getProperty($package, 'replacementPackage'));
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
