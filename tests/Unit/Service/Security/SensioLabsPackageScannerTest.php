<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Security;

use Buddy\Repman\Message\Security\SendScanResult;
use Buddy\Repman\Repository\ScanResultRepository;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\Security\PackageScanner\SensioLabsPackageScanner;
use Buddy\Repman\Service\Security\SecurityChecker;
use Buddy\Repman\Tests\MotherObject\PackageMother;
use Munus\Control\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;

final class SensioLabsPackageScannerTest extends TestCase
{
    const VERSION = '1.2.3';

    private SensioLabsPackageScanner $scanner;
    private SecurityChecker $checkerMock;
    private string $baseDir;
    /** @var ScanResultRepository|MockObject */
    private $repoMock;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->checkerMock = $this->createMock(SecurityChecker::class);
        $this->checkerMock->method('check')->willReturn([]);
        $this->repoMock = $this->createMock(ScanResultRepository::class);

        $this->filesystem = new Filesystem();
        $this->baseDir = sys_get_temp_dir().'/repman';

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
    }

    public function testSuccessfulScanNoAdvisories(): void
    {
        $this->repoMock
            ->expects(self::once())
            ->method('add');

        $package = PackageMother::synchronized('buddy-works/repman', self::VERSION);

        $this->scanner->scan($package);
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
    }

    public function testLockFileNotFound(): void
    {
        $this->repoMock
            ->expects(self::once())
            ->method('add');

        $package = PackageMother::synchronized('buddy-works/repman', self::VERSION);

        $this->prepareScanner('repman-no-lock')->scan($package);
    }

    public function testBrokenZip(): void
    {
        $this->repoMock
            ->expects(self::once())
            ->method('add');

        $package = PackageMother::synchronized('buddy-works/repman', self::VERSION);

        $this->prepareScanner('repman-invalid-archive')->scan($package);
    }

    private function prepareScanner(string $fixtureType = 'repman'): SensioLabsPackageScanner
    {
        $distFile = realpath(__DIR__.'/../../../Resources/fixtures/buddy/dist/buddy-works/'.$fixtureType.'/1.2.3.0_ac7dcaf888af2324cd14200769362129c8dd8550.zip');
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('findProviders')->willReturn(['buddy-works/repman' => [self::VERSION => [
            'version' => self::VERSION,
            'dist' => [
                'type' => 'zip',
                'url' => $distFile,
                'reference' => 'ac7dcaf888af2324cd14200769362129c8dd8550',
            ],
            'version_normalized' => '1.2.3.0',
        ]]]);

        $packageManager->method('distFilename')->willReturn(Option::some($distFile));

        $messageBusMock = $this->createMock(MessageBus::class);
        $messageBusMock
            ->method('dispatch')
            ->willReturn(new Envelope(new SendScanResult(['test@example.com'], 'buddy', 'test/test', 'test', [])));

        return new SensioLabsPackageScanner(
            $this->checkerMock,
            $packageManager,
            $this->repoMock,
            $messageBusMock
        );
    }
}
