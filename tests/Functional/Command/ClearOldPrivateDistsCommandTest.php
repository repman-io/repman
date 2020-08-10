<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ClearOldPrivateDistsCommand;
use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Repository\VersionRepository;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearOldPrivateDistsCommandTest extends FunctionalTestCase
{
    private string $ref = 'ac7dcaf888af2324cd14200769362129c8dd8550';
    private string $packageName = 'buddy-works/repman';
    private string $version = '1.2.3';

    public function testSuccessfulCleanupAfter30DaysWithoutDownloads(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId);

        $lastSyncAt = new \DateTime();
        $lastSyncAt->modify('-30 days');

        $this->fixtures->syncPackageWithData($packageId, $this->packageName, 'description', $this->version, new \DateTimeImmutable(), $this->prepareVersions(), $lastSyncAt);
        $this->fixtures->prepareRepoFiles();
        $commandTester = new CommandTester(
            $this->container()->get(ClearOldPrivateDistsCommand::class)
        );

        self::assertFileExists($this->distFilePath());
        self::assertCount(1, $this->container()->get(VersionRepository::class)->findAll());
        self::assertEquals(0, $commandTester->execute([]));
        self::assertCount(0, $this->container()->get(VersionRepository::class)->findAll());
        self::assertFileNotExists($this->distFilePath());

        $this->fixtures->prepareRepoFiles();
    }

    public function testNoCleanupWhenPackageIsNew(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId);
        $this->fixtures->syncPackageWithData($packageId, $this->packageName, 'description', $this->version, new \DateTimeImmutable(), $this->prepareVersions());
        $this->fixtures->prepareRepoFiles();
        $commandTester = new CommandTester(
            $this->container()->get(ClearOldPrivateDistsCommand::class)
        );

        self::assertFileExists($this->distFilePath());
        self::assertCount(1, $this->container()->get(VersionRepository::class)->findAll());
        self::assertEquals(0, $commandTester->execute([]));
        self::assertCount(1, $this->container()->get(VersionRepository::class)->findAll());
        self::assertFileExists($this->distFilePath());

        $this->fixtures->prepareRepoFiles();
    }

    public function testSuccessfulCleanupAfter30DaysWithDownloads(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId);

        $lastSyncAt = new \DateTime();
        $lastSyncAt->modify('-30 days');

        $this->fixtures->syncPackageWithData($packageId, $this->packageName, 'description', $this->version, new \DateTimeImmutable(), $this->prepareVersions(), $lastSyncAt);
        $this->fixtures->addPackageDownload(1, $packageId, $this->version, \DateTimeImmutable::createFromMutable($lastSyncAt));

        $this->fixtures->prepareRepoFiles();
        $commandTester = new CommandTester(
            $this->container()->get(ClearOldPrivateDistsCommand::class)
        );

        self::assertFileExists($this->distFilePath());
        self::assertCount(1, $this->container()->get(VersionRepository::class)->findAll());
        self::assertEquals(0, $commandTester->execute([]));
        self::assertCount(0, $this->container()->get(VersionRepository::class)->findAll());
        self::assertFileNotExists($this->distFilePath());

        $this->fixtures->prepareRepoFiles();
    }

    public function testNoCleanupWithFreshDownloads(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId);

        $lastSyncAt = new \DateTime();
        $lastSyncAt->modify('-30 days');

        $lastDownload = new \DateTime();
        $lastDownload->modify('-29 days');

        $this->fixtures->syncPackageWithData($packageId, $this->packageName, 'description', $this->version, new \DateTimeImmutable(), $this->prepareVersions(), $lastSyncAt);
        $this->fixtures->addPackageDownload(1, $packageId, $this->version, \DateTimeImmutable::createFromMutable($lastDownload));

        $this->fixtures->prepareRepoFiles();
        $commandTester = new CommandTester(
            $this->container()->get(ClearOldPrivateDistsCommand::class)
        );

        self::assertFileExists($this->distFilePath());
        self::assertCount(1, $this->container()->get(VersionRepository::class)->findAll());
        self::assertEquals(0, $commandTester->execute([]));
        self::assertCount(1, $this->container()->get(VersionRepository::class)->findAll());
        self::assertFileExists($this->distFilePath());

        $this->fixtures->prepareRepoFiles();
    }

    private function distFilePath(): string
    {
        return $this->container()->getParameter('dists_dir')
            .'/buddy/dist/'.$this->packageName.'/'.$this->version.'.0_'.$this->ref.'.zip';
    }

    /**
     * @return Version[]
     */
    private function prepareVersions(): array
    {
        return [
            new Version(Uuid::uuid4(), $this->version, $this->ref, 1234, new \DateTimeImmutable()),
        ];
    }
}
