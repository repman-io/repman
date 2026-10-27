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
    private SecurityChecker&MockObject $checkerMock;
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

    public function testSkipPackageWithSecurityScanDisabled(): void
    {
        $this->repoMock->expects(self::never())->method('add');
        $this->checkerMock->expects(self::never())->method('check');

        $package = PackageMother::synchronized('buddy-works/repman', '1.0.0');
        $package->update($package->repositoryUrl(), $package->keepLastReleases(), false);

        $this->scanner->scan($package);
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

    public function testLockFileAtTheRootOfAnArtifact(): void
    {
        $package = PackageMother::synchronized('buddy-works/repman', self::VERSION);

        $this->checkerMock = $this->createMock(SecurityChecker::class);
        $this->checkerMock->expects(self::once())->method('check')
            ->with(self::stringContains('"packages"'))
            ->willReturn(['vendor/some-dependency' => ['version' => '6.6.6', 'advisories' => [['title' => 'Compromised']]]]);
        $this->prepareScanner('repman-root-lock')->scan($package);

        $this->assertPackageSecurity('warning', $package);
        self::assertSame(['/composer.lock'], array_keys((array) (new \ReflectionProperty($package, 'lastScanResult'))->getValue($package)));
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

    /**
     * @param array<string, array<string, mixed>>|null $versions     provider data of buddy-works/repman, a stable 1.2.3 by default
     * @param string[]                                 $distVersions collects the versions the scanner asks the dist of
     * @param string|null                              $sourceLock   composer.lock the sync kept for 1.2.3 at source reference "abc123"
     */
    private function prepareScanner(string $fixtureType = 'repman', ?array $versions = null, array &$distVersions = [], ?string $sourceLock = null): SensioLabsPackageScanner
    {
        $distFile = \realpath(__DIR__.'/../../../Resources/fixtures/buddy/dist/buddy-works/'.$fixtureType.'/1.2.3.0_ac7dcaf888af2324cd14200769362129c8dd8550.zip');
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('findProviders')->willReturn(
            [
                new \DateTimeImmutable(),
                [
                    'buddy-works/repman' => $versions ?? [
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

        $packageManager->method('sourceLock')->willReturnCallback(
            fn (string $organization, string $package, string $version, string $ref): ?string => [$package, $version, $ref] === ['buddy-works/repman', '1.2.3.0', 'abc123'] ? $sourceLock : null
        );
        $packageManager->method('distFilename')->willReturnCallback(function (string $organization, string $package, string $version) use ($distFile, &$distVersions): Option {
            $distVersions[] = $version;

            return Option::some($distFile);
        });

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

    public function testScanDefaultBranchWhenThereIsNoStableRelease(): void
    {
        $distVersions = [];
        $scanner = $this->prepareScanner('repman', [
            '1.0.0-beta' => self::devVersion('1.0.0-beta', '1.0.0.0-beta'),
            'dev-main' => self::devVersion('dev-main', 'dev-main') + ['default-branch' => true],
        ], $distVersions);
        $package = PackageMother::synchronized('buddy-works/repman', 'no stable release');

        $scanner->scan($package);

        $this->assertPackageSecurity('ok', $package);
        self::assertSame(['dev-main'], $distVersions);
    }

    public function testScanDevMasterSyncedBeforeComposer2(): void
    {
        $distVersions = [];
        $scanner = $this->prepareScanner('repman', ['dev-master' => self::devVersion('dev-master', '9999999-dev')], $distVersions);
        $package = PackageMother::synchronized('buddy-works/repman', 'no stable release');

        $scanner->scan($package);

        $this->assertPackageSecurity('ok', $package);
        self::assertSame(['9999999-dev'], $distVersions);
    }

    public function testVersionInstalledFromSourceIsScannedFromTheLockTheSyncKept(): void
    {
        $this->checkerMock = $this->createMock(SecurityChecker::class);
        $this->checkerMock->expects(self::once())->method('check')->with('{"packages":[]}')
            ->willReturn(['vendor/some-dependency' => ['version' => '6.6.6', 'advisories' => [['title' => 'Compromised']]]]);
        $distVersions = [];
        $scanner = $this->prepareScanner('repman', [self::VERSION => [
            'version' => self::VERSION,
            'version_normalized' => '1.2.3.0',
            'source' => ['type' => 'git', 'url' => 'git@git.example.com:buddy/repman.git', 'reference' => 'abc123'],
        ]], $distVersions, '{"packages":[]}');
        $package = PackageMother::synchronized('buddy-works/repman', self::VERSION);

        $scanner->scan($package);

        $this->assertPackageSecurity('warning', $package);
        self::assertSame([], $distVersions);
    }

    public function testVersionWithoutDistIsNotAvailable(): void
    {
        $scanner = $this->prepareScanner('repman', [self::VERSION => ['version' => self::VERSION, 'version_normalized' => '1.2.3.0', 'source' => ['type' => 'git']]]);
        $package = PackageMother::synchronized('buddy-works/repman', self::VERSION);

        $scanner->scan($package);

        $this->assertPackageSecurity('n/a', $package);
    }

    /**
     * @return array<string, mixed>
     */
    private static function devVersion(string $version, string $normalized): array
    {
        return [
            'version' => $version,
            'version_normalized' => $normalized,
            'dist' => ['type' => 'zip', 'url' => '/dist.zip', 'reference' => 'ac7dcaf888af2324cd14200769362129c8dd8550'],
        ];
    }

    private function assertPackageSecurity(string $expected, Package $package): void
    {
        $reflection = new \ReflectionObject($package);
        $property = $reflection->getProperty('lastScanStatus');
        $property->setAccessible(true);

        self::assertEquals($expected, $property->getValue($package));
    }
}
