<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\PackageSynchronizer;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Service\PackageSynchronizer\ComposerPackageSynchronizer;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ComposerPackageSynchronizerTest extends TestCase
{
    public function testSynchronizePackageFromLocalPath(): void
    {
        $dir = sys_get_temp_dir().'/repman';
        $path = $dir.'/local/buddy-works/repman.json';
        @unlink($path);
        $synchronizer = new ComposerPackageSynchronizer($dir);

        $package = new Package(Uuid::uuid4(), 'path', __DIR__.'/../../../../');
        $synchronizer->synchronize($package);

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertTrue($json['packages']['buddy-works/repman'] !== []);
        @unlink($path);
    }

    public function testSynchronizeError(): void
    {
        $dir = sys_get_temp_dir().'/repman';
        $synchronizer = new ComposerPackageSynchronizer($dir);

        $package = new Package(Uuid::uuid4(), 'artifact', '/non/exist/path');
        $synchronizer->synchronize($package);
        // exception was not throw
        self::assertTrue(true);
    }

    public function testSynchronizePackageFromArtifacts(): void
    {
        $dir = sys_get_temp_dir().'/repman';
        $path = $dir.'/local/buddy-works/alpha.json';
        @unlink($path);
        $synchronizer = new ComposerPackageSynchronizer($dir);

        $package = new Package(Uuid::uuid4(), 'artifact', __DIR__.'/../../../Resources/artifacts');
        $synchronizer->synchronize($package);

        self::assertFileExists($path);

        $json = unserialize((string) file_get_contents($path));
        self::assertCount(4, $json['packages']['buddy-works/alpha']);
        @unlink($path);
    }
}
