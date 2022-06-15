<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\MessageHandler\Security;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\MessageHandler\Security\ScanPackageHandler;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Security\PackageScanner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ScanPackageHandlerTest extends TestCase
{
    /**
     * @var MockObject&PackageScanner
     */
    private PackageScanner $packageScanner;
    /**
     * @var MockObject&PackageRepository
     */
    private PackageRepository $packageRepository;

    protected function setUp(): void
    {
        $this->packageScanner = $this->createMock(PackageScanner::class);
        $this->packageRepository = $this->createMock(PackageRepository::class);
    }

    public function testHandlerIgnoresNonExistentPackages(): void
    {
        $this->packageScanner->expects(self::never())
            ->method('scan');
        $this->packageRepository->expects(self::once())
            ->method('find')
            ->willReturn(null);

        $packageToScan = new ScanPackage('ae8b3351-7874-40e0-a245-ae2f4c921038');
        $handler = new ScanPackageHandler($this->packageScanner, $this->packageRepository);
        $handler($packageToScan);
    }

    public function testHandlerIgnoresPackageWithSecurityScanOptionDisabled(): void
    {
        $package = $this->getPackage(false);
        $this->packageScanner->expects(self::never())
            ->method('scan');
        $this->packageRepository->expects(self::once())
            ->method('find')
            ->willReturn($package);

        $packageToScan = new ScanPackage('ae8b3351-7874-40e0-a245-ae2f4c921038');
        $handler = new ScanPackageHandler($this->packageScanner, $this->packageRepository);
        $handler($packageToScan);
    }

    public function testHandlerScansPackageWithSecurityScanOptionEnabled(): void
    {
        $package = $this->getPackage(true);
        $this->packageScanner->expects(self::once())
            ->method('scan')
            ->with($package);
        $this->packageRepository->expects(self::once())
            ->method('find')
            ->willReturn($package);

        $packageToScan = new ScanPackage('ae8b3351-7874-40e0-a245-ae2f4c921038');
        $handler = new ScanPackageHandler($this->packageScanner, $this->packageRepository);
        $handler($packageToScan);
    }

    /**
     * Helper method to create Package instance.
     */
    private function getPackage(bool $enableSecurityScan): Package
    {
        return new Package(
            Uuid::fromString('ae8b3351-7874-40e0-a245-ae2f4c921038'),
            'type',
            'http://url',
            [],
            0,
            $enableSecurityScan
        );
    }
}
