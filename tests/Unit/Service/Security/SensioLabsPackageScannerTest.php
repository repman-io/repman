<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Security;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Security\SendScanResult;
use Buddy\Repman\Repository\ScanResultRepository;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\Security\PackageScanner\SensioLabsPackageScanner;
use Buddy\Repman\Service\Security\SecurityChecker;
use Buddy\Repman\Tests\MotherObject\PackageMother;
use Munus\Control\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;

final class SensioLabsPackageScannerTest extends TestCase
{
    /** @var string */
    private const VERSION = '1.2.3';

    private SensioLabsPackageScanner $scanner;
    private SecurityChecker $checkerMock;
    /** @var ScanResultRepository|MockObject */
    private $repoMock;

    protected function setUp(): void
    {
        $this->checkerMock = $this->createMock(SecurityChecker::class);
        $this->checkerMock->method('check')->willReturn([]);
        $this->repoMock = $this->createMock(ScanResultRepository::class);

        $this->scanner = $this->prepareScanner();
    }

    public function testScanPackageNotYetSynchronized(): void
    {
        $this->repoMock
            ->expects(self::never())
            ->method('add');

        $this->scanner->scan(PackageMother::withOrganization('path', '', 'buddy'));
    }

    public function testScanVersionNotFound(): void
    {
        $this->repoMock
            ->expects(self::once())
            ->method('add');

        $package = PackageMother::synchronized('buddy-works/repman', '1.0.0', '');

        $this->scanner->scan($package);

        $this->assertPackageSecurity('error', $package);
    }

    public function testSuccessfulScanNoAdvisories(): void
    {
        $this->repoMock
            ->expects(self::once())
            ->method('add');

        $package = PackageMother::synchronized('buddy-works/repman', self::VERSION);

        $this->scanner->scan($package);

        $this->assertPackageSecurity('ok', $package);
    }

    public function testSuccessfulScanWithAdvisories(): void
    {
        $this->repoMock
            ->expects(self::once())
            ->method('add');

        $result = [
            'vendor/some-dependency' => [
                'version' => '6.6.6',
                'advisories' => [
                    ['title' => 'Compromised'],
                ],
            ],
        ];

        $package = PackageMother::synchronized('buddy-works/repman', self::VERSION);

        $this->checkerMock = $this->createMock(SecurityChecker::class);
        $this->checkerMock->method('check')->willReturn($result);
        $this->prepareScanner()->scan($package);

        $this->assertPackageSecurity('warning', $package);
    }

    public function testLockFileNotFound(): void
    {
        $this->repoMock
            ->expects(self::once())
            ->method('add');

        $package = PackageMother::synchronized('buddy-works/repman', self::VERSION);

        $this->prepareScanner('repman-no-lock')->scan($package);

        $this->assertPackageSecurity('n/a', $package);
    }

    public function testBrokenZip(): void
    {
        $this->repoMock
            ->expects(self::once())
            ->method('add');

        $package = PackageMother::synchronized('buddy-works/repman', self::VERSION);

        $this->prepareScanner('repman-invalid-archive')->scan($package);

        $this->assertPackageSecurity('error', $package);
    }

    private function prepareScanner(string $fixtureType = 'repman'): SensioLabsPackageScanner
    {
        $distFile = \realpath(__DIR__.'/../../../Resources/fixtures/buddy/dist/buddy-works/'.$fixtureType.'/1.2.3.0_ac7dcaf888af2324cd14200769362129c8dd8550.zip');
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('findProviders')->willReturn(
            [
                new \DateTimeImmutable(),
                [
                    'buddy-works/repman' => [
                        self::VERSION => [
                            'version' => self::VERSION,
                            'dist' => [
                                'type' => 'zip',
                                'url' => $distFile,
                                'reference' => 'ac7dcaf888af2324cd14200769362129c8dd8550',
                            ],
                            'version_normalized' => '1.2.3.0',
                        ],
                    ],
                ],
            ]
        );

        $packageManager->method('distFilename')->willReturn(Option::some($distFile));

        $messageBusMock = $this->createMock(MessageBus::class);
        $messageBusMock
            ->method('dispatch')
            ->willReturn(new Envelope(new SendScanResult(['test@example.com'], 'buddy', 'test/test', 'test', [])));

        $distStorage = $this->createMock(Storage::class);
        $tempFilename = \tempnam(\sys_get_temp_dir(), 'repman-test');
        self::assertNotFalse($tempFilename, 'Error while creating temp file for testing');
        self::assertNotFalse($distFile, 'Could not determined the path to dist file.');
        \file_put_contents($tempFilename, \file_get_contents($distFile));
        $distStorage->method('getLocalFileForDistUrl')->willReturn(Option::of($tempFilename));

        return new SensioLabsPackageScanner(
            $this->checkerMock,
            $packageManager,
            $this->repoMock,
            $messageBusMock,
            $distStorage
        );
    }

    private function assertPackageSecurity(string $expected, Package $package): void
    {
        $reflection = new \ReflectionObject($package);
        $property = $reflection->getProperty('lastScanStatus');
        $property->setAccessible(true);

        self::assertEquals($expected, $property->getValue($package));
    }
}
