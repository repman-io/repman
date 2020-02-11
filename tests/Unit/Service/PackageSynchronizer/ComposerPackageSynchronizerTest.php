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
}
