<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ClearMetadataCacheCommand;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearMetadataCacheCommandTest extends FunctionalTestCase
{
    public function testClearMetadataCache(): void
    {
        $basePath = sys_get_temp_dir().'/'.'repman';
        $this->prepareTempFiles(
            $packagesFile = $basePath.'/packagist.org/packages.json',
            $distFile = $basePath.'dist/a/b/dist.zip'
        );

        self::assertTrue(file_exists($packagesFile));
        self::assertTrue(file_exists($distFile));

        $command = new ClearMetadataCacheCommand($basePath);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertFalse(file_exists($packagesFile));
        self::assertTrue(file_exists($distFile));
        self::assertEquals("Deleted 1 file(s).\n", $commandTester->getDisplay());
    }

    private function prepareTempFiles(string $packagesFile, string $distFile): void
    {
        $this->ensureDirExist($packagesFile);
        $this->ensureDirExist($distFile);

        file_put_contents($packagesFile, '{"packages":[]}');
        file_put_contents($distFile, 'zip content');
    }

    private function ensureDirExist(string $path): void
    {
        $path = dirname($path);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}
