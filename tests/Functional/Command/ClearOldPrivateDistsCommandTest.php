<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ClearOldPrivateDistsCommand;
use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Composer\Semver\VersionParser;
use League\Flysystem\FilesystemInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearOldPrivateDistsCommandTest extends FunctionalTestCase
{
    private string $ref = 'ac7dcaf888af2324cd14200769362129c8dd8550';
    private string $packageName = 'buddy-works/repman';
    private string $version = '1.2.3';

    private FilesystemInterface $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->container()->get('repo.storage');
    }

    public function testWillNotRemoveStableVersion(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId);

        $this->fixtures->syncPackageWithData(
            $packageId,
            $this->packageName,
            'description',
            $this->version,
            new \DateTimeImmutable(),
            [
                $this->createVersion($this->version, $this->ref, Version::STABILITY_STABLE),
            ]
        );
        $this->fixtures->prepareRepoFiles();
        $commandTester = new CommandTester(
            $this->container()->get(ClearOldPrivateDistsCommand::class)
        );

        $this->assertFileExistence($this->distFilePath($this->version, $this->ref));

        self::assertEquals(0, $commandTester->execute([]));

        $this->assertFileExistence($this->distFilePath($this->version, $this->ref));

        $this->fixtures->prepareRepoFiles();
    }

    public function testWillNotRemoveLastDevVersion(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId);
        $devRef = sha1(uniqid());

        $this->fixtures->syncPackageWithData(
            $packageId,
            $this->packageName,
            'description',
            $this->version,
            new \DateTimeImmutable(),
            [
                $this->createVersion($this->version, $this->ref, Version::STABILITY_STABLE),
                $this->createVersion('dev-master', $devRef, 'dev'),
            ]
        );
        $this->fixtures->prepareRepoFiles();
        $this->prepareTestDist($this->distFilePath('dev-master', $devRef));

        $commandTester = new CommandTester(
            $this->container()->get(ClearOldPrivateDistsCommand::class)
        );

        $this->assertFileExistence($this->distFilePath($this->version, $this->ref));
        $this->assertFileExistence($this->distFilePath('dev-master', $devRef));

        self::assertEquals(0, $commandTester->execute([]));

        $this->assertFileExistence($this->distFilePath($this->version, $this->ref));
        $this->assertFileExistence($this->distFilePath('dev-master', $devRef));

        $this->fixtures->prepareRepoFiles();
    }

    public function testWillRemoveAllDevVersionsExceptLast(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId);
        $dev1Ref = sha1(uniqid());
        $dev2Ref = sha1(uniqid());
        $dev3Ref = sha1(uniqid());
        $dev4Ref = sha1(uniqid());
        $dev5Ref = sha1(uniqid());

        $this->fixtures->syncPackageWithData(
            $packageId,
            $this->packageName,
            'description',
            $this->version,
            new \DateTimeImmutable(),
            [
                $this->createVersion($this->version, $this->ref, Version::STABILITY_STABLE),
                $this->createVersion('dev-master', $dev1Ref, 'dev', 1),
                $this->createVersion('dev-master', $dev2Ref, 'dev', 2),
                $this->createVersion('dev-test', $dev3Ref, 'dev', 3),
                $this->createVersion('dev-stage', $dev4Ref, 'dev', 4),
                $this->createVersion('dev-stage', $dev5Ref, 'dev', 5),
            ]
        );
        $this->fixtures->prepareRepoFiles();
        $this->prepareTestDist($this->distFilePath('dev-master', $dev1Ref));
        $this->prepareTestDist($this->distFilePath('dev-master', $dev2Ref));
        $this->prepareTestDist($this->distFilePath('dev-test', $dev3Ref));
        $this->prepareTestDist($this->distFilePath('dev-stage', $dev4Ref));
        $this->prepareTestDist($this->distFilePath('dev-stage', $dev5Ref));

        $commandTester = new CommandTester(
            $this->container()->get(ClearOldPrivateDistsCommand::class)
        );

        $this->assertFileExistence($this->distFilePath($this->version, $this->ref));
        $this->assertFileExistence($this->distFilePath('dev-master', $dev1Ref));
        $this->assertFileExistence($this->distFilePath('dev-master', $dev2Ref));
        $this->assertFileExistence($this->distFilePath('dev-test', $dev3Ref));
        $this->assertFileExistence($this->distFilePath('dev-stage', $dev4Ref));
        $this->assertFileExistence($this->distFilePath('dev-stage', $dev5Ref));

        self::assertEquals(0, $commandTester->execute([]));

        $this->assertFileExistence($this->distFilePath($this->version, $this->ref));
        self::assertFileDoesNotExist($this->distFilePath('dev-master', $dev1Ref));
        $this->assertFileExistence($this->distFilePath('dev-master', $dev2Ref));
        $this->assertFileExistence($this->distFilePath('dev-test', $dev3Ref));
        self::assertFileDoesNotExist($this->distFilePath('dev-stage', $dev4Ref));
        $this->assertFileExistence($this->distFilePath('dev-stage', $dev5Ref));

        $this->fixtures->prepareRepoFiles();
    }

    private function distFilePath(string $version, string $ref): string
    {
        return 'buddy/dist/'
            .$this->packageName.'/'
            .(new VersionParser())->normalize($version)
            .'_'.$ref.'.zip';
    }

    private function prepareTestDist(string $path): void
    {
        $this->filesystem->write($path, 'test dist');
    }

    private function createVersion(string $version, string $ref, string $stability, int $dateOffset = 0): Version
    {
        return new Version(
            Uuid::uuid4(),
            $version,
            $ref,
            1234,
            \DateTimeImmutable::createFromMutable((new \DateTime())->modify("+$dateOffset seconds")),
            $stability
        );
    }

    private function assertFileExistence(string $filepath): void
    {
        $message = \sprintf('Failed asserting that file "%s" exists.', $filepath);
        self::assertTrue(
            $this->filesystem->has($filepath),
            $message
        );
    }
}
