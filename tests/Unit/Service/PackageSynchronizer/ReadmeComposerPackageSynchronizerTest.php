<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\PackageSynchronizer;

use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\PackageNormalizer;
use Buddy\Repman\Service\PackageSynchronizer\ComposerPackageSynchronizer;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use Buddy\Repman\Tests\MotherObject\PackageMother;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;

final class ReadmeComposerPackageSynchronizerTest extends TestCase
{
    private ComposerPackageSynchronizer $synchronizer;
    private string $resourcesDir;
    private string $path;

    protected function setUp(): void
    {
        $baseDir = \sys_get_temp_dir().'/repman';
        $repoFilesystem = new Filesystem(new LocalFilesystemAdapter($baseDir));
        $fileStorage = new Storage(new FakeDownloader(''), $repoFilesystem);
        $this->synchronizer = new ComposerPackageSynchronizer(
            new PackageManager(
                $fileStorage,
                $repoFilesystem
            ),
            new PackageNormalizer(),
            self::createStub(PackageRepository::class),
            $fileStorage,
            self::createStub(UserOAuthTokenRefresher::class),
            'gitlab.com'
        );
        $this->resourcesDir = dirname(__DIR__, 3).'/Resources/';

        $this->path = $baseDir.'/buddy/p/buddy-works/alpha.json';
        @\unlink($this->path);
    }

    protected function tearDown(): void
    {
        @\unlink($this->path);
    }

    public function testNoReadme(): void
    {
        $package = PackageMother::withOrganization(
            'artifact',
            $this->resourcesDir.'readme-artifacts/no-readme',
            'buddy'
        );
        $this->synchronizer->synchronize($package);

        self::assertFileExists($this->path);

        $json = \unserialize((string) \file_get_contents($this->path));
        self::assertCount(1, $json['packages']['buddy-works/alpha']);

        self::assertCount(1, $package->versions());
        self::assertSame('1.2.0', $package->latestReleasedVersion());
        self::assertNull($package->readme());
    }

    public function testReadme(): void
    {
        $package = PackageMother::withOrganization(
            'artifact',
            $this->resourcesDir.'readme-artifacts/readme',
            'buddy'
        );
        $this->synchronizer->synchronize($package);

        self::assertFileExists($this->path);

        $json = \unserialize((string) \file_get_contents($this->path));
        self::assertCount(3, $json['packages']['buddy-works/alpha']);

        self::assertCount(3, $package->versions());
        self::assertSame('1.3.0', $package->latestReleasedVersion());
        self::assertSame("<h1><a id=\"user-content-test\" href=\"#test\" class=\"\" aria-hidden=\"true\" tabindex=\"-1\" title=\"\"></a>Test</h1>\n<p>Testing</p>\n", $package->readme());
    }

    public function testCaseInsensitiveReadme(): void
    {
        $package = PackageMother::withOrganization(
            'artifact',
            $this->resourcesDir.'readme-artifacts/wrong-case-readme',
            'buddy'
        );
        $this->synchronizer->synchronize($package);

        self::assertFileExists($this->path);

        $json = \unserialize((string) \file_get_contents($this->path));
        self::assertCount(1, $json['packages']['buddy-works/alpha']);

        self::assertCount(1, $package->versions());
        self::assertSame('1.3.0', $package->latestReleasedVersion());
        self::assertSame("<h1><a id=\"user-content-test\" href=\"#test\" class=\"\" aria-hidden=\"true\" tabindex=\"-1\" title=\"\"></a>Test</h1>\n<p>Testing</p>\n", $package->readme());
    }

    /**
     * Artifacts imported should have the files in the top level directory
     * Copies from VCS have a top level directory of the project name & commit ID
     * This will test loading the files from that second directory, but will use artifact
     * to make the test simpler.
     */
    public function testReadmeInSecondLevelDirectory(): void
    {
        $package = PackageMother::withOrganization(
            'artifact',
            $this->resourcesDir.'readme-artifacts/readme-in-second-dir',
            'buddy'
        );
        $this->synchronizer->synchronize($package);

        self::assertFileExists($this->path);

        $json = \unserialize((string) \file_get_contents($this->path));
        self::assertCount(1, $json['packages']['buddy-works/alpha']);

        self::assertCount(1, $package->versions());
        self::assertSame('1.3.0', $package->latestReleasedVersion());
        self::assertSame("<h1><a id=\"user-content-test\" href=\"#test\" class=\"\" aria-hidden=\"true\" tabindex=\"-1\" title=\"\"></a>Test</h1>\n<p>Testing</p>\n", $package->readme());
    }

    public function testUnstableVersion(): void
    {
        $package = PackageMother::withOrganization(
            'artifact',
            $this->resourcesDir.'readme-artifacts/no-stable-release',
            'buddy'
        );
        $this->synchronizer->synchronize($package);

        self::assertFileExists($this->path);

        $json = \unserialize((string) \file_get_contents($this->path));
        self::assertCount(1, $json['packages']['buddy-works/alpha']);

        self::assertCount(1, $package->versions());
        self::assertSame('no stable release', $package->latestReleasedVersion());
        self::assertSame("<h1><a id=\"user-content-test\" href=\"#test\" class=\"\" aria-hidden=\"true\" tabindex=\"-1\" title=\"\"></a>Test</h1>\n<p>Testing</p>\n", $package->readme());
    }
}
