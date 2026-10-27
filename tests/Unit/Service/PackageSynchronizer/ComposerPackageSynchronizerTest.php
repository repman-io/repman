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
use Composer\IO\BufferIO;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

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
        $repoFilesystem = new Filesystem(new LocalFilesystemAdapter($this->baseDir));
        $this->downloader = new FakeDownloader();
        $distStorage = new Storage($this->downloader, $repoFilesystem);
        $this->synchronizer = new ComposerPackageSynchronizer(
            new PackageManager($distStorage, $repoFilesystem),
            new PackageNormalizer(),
            $this->repoMock = $this->createMock(PackageRepository::class),
            $distStorage,
            self::createStub(UserOAuthTokenRefresher::class),
            'gitlab.com'
        );
        $this->resourcesDir = dirname(__DIR__, 3).'/Resources/';
    }

    public function testSynchronizePackageFromLocalPath(): void
    {
        $path = $this->baseDir.'/buddy/p/repman-io/repman.json';
        @unlink($path);
        // left by runs from before path dists were skipped
        $stale = glob($this->baseDir.'/buddy/dist/repman-io/repman/*.path');
        array_map(unlink(...), is_array($stale) ? $stale : []);

        // a plain directory (no VCS), so the result does not depend on the git state of this checkout
        $basePath = sys_get_temp_dir().'/repman-path-'.uniqid('', true);
        mkdir($basePath);
        file_put_contents($basePath.'/composer.json', (string) json_encode(['name' => 'repman-io/repman', 'version' => '1.2.3']));

        $package = PackageMother::withOrganization('path', $basePath, 'buddy');
        $this->synchronizer->synchronize($package);

        self::assertTrue($package->isSynchronizedSuccessfully(), (string) $this->getProperty($package, 'lastSyncError'));
        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertSame(['1.2.3'], array_keys($json['packages']['repman-io/repman']));
        @unlink($path);
        unlink($basePath.'/composer.json');
        rmdir($basePath);

        self::assertCount(1, $package->versions());
        // the directory is not archived: there is no dist to download (the version is installed from the path)
        $version = $package->versions()->first();
        self::assertInstanceOf(Version::class, $version);
        self::assertSame(0, $version->size());
        self::assertSame([], glob($this->baseDir.'/buddy/dist/repman-io/repman/*.path'));
    }

    public function testSynchronizeGitPackageWithoutDistKeepsTheLockFileOfTheScannedVersions(): void
    {
        $repoDir = sys_get_temp_dir().'/repman-git-'.uniqid('', true);
        mkdir($repoDir);
        $git = static function (string ...$command) use ($repoDir): string {
            return new Process(['git', '-c', 'user.name=Test', '-c', 'user.email=test@example.com', '-c', 'init.defaultBranch=main', ...$command], $repoDir)->mustRun()->getOutput();
        };
        file_put_contents($repoDir.'/composer.json', (string) json_encode(['name' => 'buddy-works/locked-app']));
        file_put_contents($repoDir.'/composer.lock', '{"packages":[{"name":"old/dep","version":"1.0.0"}]}');
        $git('init');
        $git('add', '.');
        $git('commit', '-m', 'initial');
        $git('tag', '1.0.0');
        file_put_contents($repoDir.'/composer.lock', '{"packages":[{"name":"new/dep","version":"2.0.0"}]}');
        $git('commit', '-am', 'update');
        $git('tag', '1.1.0');
        $ref = trim($git('rev-parse', 'HEAD'));

        $package = PackageMother::withOrganization('git', $repoDir, 'buddy');
        $this->synchronizer->synchronize($package);

        self::assertTrue($package->isSynchronizedSuccessfully(), (string) $this->getProperty($package, 'lastSyncError'));
        $dir = $this->baseDir.'/buddy/dist/buddy-works/locked-app';
        // the latest release and the default branch are what the scan reads, older tags are not
        self::assertStringEqualsFile($dir.'/1.1.0.0_'.$ref.'.lock', '{"packages":[{"name":"new/dep","version":"2.0.0"}]}');
        self::assertStringEqualsFile($dir.'/dev-main_'.$ref.'.lock', '{"packages":[{"name":"new/dep","version":"2.0.0"}]}');
        self::assertSame([], glob($dir.'/1.0.0.0_*'));

        // a new release: the locks of the previous one are dropped, nothing scans them any more
        file_put_contents($repoDir.'/composer.lock', '{"packages":[]}');
        $git('commit', '-am', 'drop dependencies');
        $git('tag', '1.2.0');
        $newRef = trim($git('rev-parse', 'HEAD'));
        $this->synchronizer->synchronize($package);

        $found = glob($dir.'/*.lock');
        self::assertIsArray($found);
        $locks = array_map(basename(...), $found);
        sort($locks);
        self::assertSame(['1.2.0.0_'.$newRef.'.lock', 'dev-main_'.$newRef.'.lock'], $locks);

        (new \Symfony\Component\Filesystem\Filesystem())->remove([$repoDir, $dir, $this->baseDir.'/buddy/p/buddy-works/locked-app.json']);
    }

    public function testSynchronizeGitPackageWithoutDistRecordsVersionsAndLinks(): void
    {
        $repoDir = sys_get_temp_dir().'/repman-git-'.uniqid('', true);
        mkdir($repoDir);
        $git = static function (string ...$command) use ($repoDir): void {
            new Process(['git', '-c', 'user.name=Test', '-c', 'user.email=test@example.com', '-c', 'init.defaultBranch=main', ...$command], $repoDir)->mustRun();
        };
        file_put_contents($repoDir.'/composer.json', (string) json_encode(['name' => 'buddy-works/plain-git', 'require' => ['psr/log' => '^3.0']]));
        $git('init');
        $git('add', 'composer.json');
        $git('commit', '-m', 'initial');
        $git('tag', '1.2.0');

        // a plain git repository has no dist, Composer installs it from source
        $package = PackageMother::withOrganization('git', $repoDir, 'buddy');
        $this->synchronizer->synchronize($package);

        self::assertTrue($package->isSynchronizedSuccessfully(), (string) $this->getProperty($package, 'lastSyncError'));
        $versions = array_map(fn (Version $version): string => $version->version(), $package->versions()->toArray());
        sort($versions);
        self::assertSame(['1.2.0', 'dev-main'], $versions);
        $release = $package->getVersion('1.2.0');
        self::assertInstanceOf(Version::class, $release);
        self::assertSame(0, $release->size());
        self::assertSame(['requires-psr/log-^3.0'], array_values(array_map(
            fn (Link $link): string => $link->type().'-'.$link->target().'-'.$link->constraint(),
            $package->links()->toArray(),
        )));

        @unlink($this->baseDir.'/buddy/p/buddy-works/plain-git.json');
        (new \Symfony\Component\Filesystem\Filesystem())->remove($repoDir);
    }

    public function testSynchronizeGitPackageWithoutReleasesKeepsLinksOfTheDefaultBranch(): void
    {
        $repoDir = sys_get_temp_dir().'/repman-git-'.uniqid('', true);
        mkdir($repoDir);
        file_put_contents($repoDir.'/composer.json', (string) json_encode(['name' => 'buddy-works/untagged', 'require' => ['psr/log' => '^3.0']]));
        foreach ([['init'], ['add', 'composer.json'], ['commit', '-m', 'initial']] as $command) {
            new Process(['git', '-c', 'user.name=Test', '-c', 'user.email=test@example.com', '-c', 'init.defaultBranch=main', ...$command], $repoDir)->mustRun();
        }

        // Composer lists the default branch twice, as dev-main and as its 9999999-dev alias
        $package = PackageMother::withOrganization('git', $repoDir, 'buddy');
        $this->synchronizer->synchronize($package);

        self::assertTrue($package->isSynchronizedSuccessfully(), (string) $this->getProperty($package, 'lastSyncError'));
        self::assertSame('no stable release', $this->getProperty($package, 'latestReleasedVersion'));
        self::assertSame(['dev-main'], array_values(array_map(fn (Version $version): string => $version->version(), $package->versions()->toArray())));
        self::assertCount(1, $package->links());

        @unlink($this->baseDir.'/buddy/p/buddy-works/untagged.json');
        (new \Symfony\Component\Filesystem\Filesystem())->remove($repoDir);
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

        // the served reference is what the dist is stored under, so the download URL resolves
        foreach ($json['packages']['buddy-works/alpha'] as $data) {
            self::assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $data['dist']['reference']);
            self::assertSame($data['dist']['shasum'], $data['dist']['reference']);
            self::assertFileExists(sprintf('%s/buddy/dist/buddy-works/alpha/%s_%s.zip', $this->baseDir, $data['version_normalized'], $data['dist']['reference']));
        }

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
        self::assertNotSame([], $json['packages']['repman-io/repman']);
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
        self::assertNotSame([], $json['packages']['repman-io/repman']);
        @unlink($path);
    }

    public function testTokenOfThePackageWinsOverAGlobalComposerAuthToken(): void
    {
        putenv('COMPOSER_AUTH={"github-oauth":{"github.com":"global-token"}}');
        try {
            $package = PackageMother::withOrganizationAndToken('github', 'https://github.com/buddy-works/repman', 'buddy');
            $call = fn (string $method, mixed ...$args): mixed => (new \ReflectionMethod($this->synchronizer, $method))->invoke($this->synchronizer, ...$args);

            /** @var BufferIO $io */
            $io = $call('createIO');
            $call('createRepository', $package, $io);
            self::assertSame('global-token', $io->getAuthentication('github.com')['username']);

            $call('authenticate', $package, $io);
            self::assertSame('secret', $io->getAuthentication('github.com')['username']);
        } finally {
            putenv('COMPOSER_AUTH');
        }
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
        self::assertNotSame([], $json['packages']['repman-io/repman']);
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
