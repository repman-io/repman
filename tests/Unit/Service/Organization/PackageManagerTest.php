<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

final class PackageManagerTest extends TestCase
{
    use ProphecyTrait;

    private PackageManager $manager;
    private string $baseDir;
    private FilesystemInterface $filesystem;

    protected function setUp(): void
    {
        $basePath = \dirname(__DIR__, 3);
        $this->filesystem = new Filesystem(new Local($basePath.'/Resources/fixtures/'));
        $this->manager = new PackageManager(
            new Storage(
                new FakeDownloader(), new Filesystem(new MemoryAdapter())
            ),
            $this->filesystem
        );
        $this->baseDir = \sys_get_temp_dir().'/repman';
    }

    public function testFindProvidersForPackage(): void
    {
        [,$providers] = $this->manager->findProviders('buddy', [
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

    public function testReturnDistributionFilenameWhenExist(): void
    {
        /** @phpstan-var mixed $storage */
        $storage = $this->prophesize(Storage::class);
        $storage->has(Argument::type(Dist::class))->willReturn(true);
        $storage->download(Argument::cetera())->shouldNotBeCalled();
        $storage->filename(Argument::type(Dist::class))->willReturn(
            __DIR__.'/../../../Resources/buddy/dist/buddy-works/repman/1.2.3.0_ac7dcaf888af2324cd14200769362129c8dd8550.zip'
        );

        $manager = new PackageManager($storage->reveal(), $this->filesystem);

        self::assertStringContainsString(
            '1.2.3.0_ac7dcaf888af2324cd14200769362129c8dd8550.zip',
            $manager->distFilename('buddy', 'buddy-works/repman', '1.2.3.0', 'ac7dcaf888af2324cd14200769362129c8dd8550', 'zip')->get()
        );
    }

    public function testRemoveProvider(): void
    {
        $manager = $this->getManagerWithLocalStorage();

        $org = 'buddy';
        $package1 = 'vendor/package1';
        $package2 = 'vendor/package2';

        $manager->saveProvider([], $org, $package1);
        $manager->saveProvider([], $org, $package2);

        self::assertFileExists($this->baseDir.'/buddy/p/'.$package1.'.json');
        self::assertFileExists($this->baseDir.'/buddy/p/'.$package2.'.json');

        $manager->removeProvider($org, $package1);

        self::assertDirectoryExists($this->baseDir.'/buddy');
        self::assertDirectoryExists(\dirname($this->baseDir.'/buddy/p/'.$package1));
        self::assertFileDoesNotExist($this->baseDir.'/buddy/p/'.$package1.'.json');
        self::assertFileExists($this->baseDir.'/buddy/p/'.$package2.'.json');
    }

    public function testRemoveDist(): void
    {
        $manager = $this->getManagerWithLocalStorage();

        $org = 'buddy';
        $package1 = 'vendor/package1';
        $package2 = 'vendor/package2';

        @\mkdir($this->baseDir.'/buddy/dist/'.$package1, 0777, true);
        @\mkdir($this->baseDir.'/buddy/dist/'.$package2, 0777, true);

        $manager->removeDist($org, $package1);

        self::assertDirectoryExists($this->baseDir.'/buddy');
        self::assertDirectoryExists($this->baseDir.'/buddy/dist/vendor');
        self::assertDirectoryDoesNotExist($this->baseDir.'/buddy/dist/'.$package1);
        self::assertDirectoryExists($this->baseDir.'/buddy/dist/'.$package2);
    }

    public function testRemoveOrganizationDir(): void
    {
        $manager = $this->getManagerWithLocalStorage();

        $org = 'buddy';
        $package = 'hello/world';

        $manager->saveProvider([], $org, $package);

        self::assertDirectoryExists($this->baseDir.'/buddy/p/hello');

        $manager->removeProvider($org, $package)
            ->removeOrganizationDir($org);

        self::assertDirectoryDoesNotExist($this->baseDir.'/buddy');
    }

    private function getManagerWithLocalStorage(): PackageManager
    {
        $repoFilesystem = new Filesystem(new Local($this->baseDir));

        return new PackageManager(
            new Storage(new FakeDownloader(), $repoFilesystem),
            $repoFilesystem
        );
    }
}
