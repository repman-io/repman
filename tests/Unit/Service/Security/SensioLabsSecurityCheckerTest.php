<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Security;

use Buddy\Repman\Service\Security\SecurityChecker\SensioLabsSecurityChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class SensioLabsSecurityCheckerTest extends TestCase
{
    private SensioLabsSecurityChecker $checker;
    private string $dbDir;
    private string $repoDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->dbDir = sys_get_temp_dir().'/repman/security-advisories';
        $this->repoDir = sys_get_temp_dir().'/repman/security-advisories-repo';
        $this->filesystem = new Filesystem();

        $this->checker = new SensioLabsSecurityChecker($this->dbDir, 'file://'.$this->repoDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->dbDir);
        $this->filesystem->remove($this->repoDir);
    }

    public function testInvalidLockFile(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid composer.lock');

        $this->checker->check('invalid');
    }

    public function testMissingDatabase(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Advisories database does not exist');

        $this->checker = new SensioLabsSecurityChecker(sys_get_temp_dir().'/bogus-security-advisories', '');
        $this->checker->check($this->insecureLock());
    }

    public function testEmptyLockFile(): void
    {
        $this->synchronizeAdvisoriesDatabase();
        self::assertEquals($this->checker->check('{}'), []);
    }

    public function testSuccessfulScanWithAlerts(): void
    {
        $this->synchronizeAdvisoriesDatabase();
        self::assertEqualsCanonicalizing($this->checker->check($this->insecureLock()), [
            'aws/aws-sdk-php' => [
                'version' => '3.2.0',
                'advisories' => [
                    [
                        'title' => 'Security Misconfiguration Vulnerability in the AWS SDK for PHP',
                        'cve' => 'CVE-2015-5723',
                        'link' => 'https://github.com/aws/aws-sdk-php/releases/tag/3.2.1',
                    ],
                ],
            ],
            'fuel/core' => [
                'version' => '1.8.0',
                'advisories' => [
                    [
                        'title' => 'ImageMagick driver does not escape all shell arguments.',
                        'cve' => '',
                        'link' => 'https://fuelphp.com/security-advisories',
                    ],
                    [
                        'title' => 'Crypt encryption compromised.',
                        'cve' => '',
                        'link' => 'https://fuelphp.com/security-advisories',
                    ],
                ],
            ],
            'symfony/http-kernel' => [
                'version' => 'v2.3.0',
                'advisories' => [
                    [
                        'title' => 'CVE-2019-18887: Use constant time comparison in UriSigner',
                        'cve' => 'CVE-2019-18887',
                        'link' => 'https://symfony.com/cve-2019-18887',
                    ],
                    [
                        'title' => 'Direct access of ESI URLs behind a trusted proxy',
                        'cve' => 'CVE-2014-5245',
                        'link' => 'https://symfony.com/cve-2014-5245',
                    ],
                    [
                        'title' => 'Esi Code Injection',
                        'cve' => 'CVE-2015-2308',
                        'link' => 'https://symfony.com/cve-2015-2308',
                    ],
                ],
            ],
        ]);
    }

    public function testSuccessfulScanWithoutAlerts(): void
    {
        $this->synchronizeAdvisoriesDatabase();
        self::assertEquals($this->checker->check($this->safeLock()), []);
    }

    public function testUpdateWhenRepoDontExist(): void
    {
        $this->createAdvisoriesDatabaseRepo();
        self::assertTrue($this->checker->update());
        // second update should return false because nothing has changed
        self::assertFalse($this->checker->update());
    }

    public function testThrowErrorWhenUpdateFails(): void
    {
        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage("'{$this->repoDir}' does not appear to be a git repository");
        $this->checker->update();
    }

    public function testUpdateWhenRepoExist(): void
    {
        $this->createAdvisoriesDatabaseRepo();
        $this->checker->update();
        $this->updateAdvisoriesDatabaseRepo();
        self::assertTrue($this->checker->update());
        // second update should return false because nothing has changed
        self::assertFalse($this->checker->update());
    }

    private function updateAdvisoriesDatabaseRepo(): void
    {
        $this->filesystem->copy($this->repoDir.'/aws/aws-sdk-php/CVE-2015-5723.yaml', $this->repoDir.'/google/google-sdk-php/CVE-2015-5723.yaml');
        $this->executeCommandInRepoDir(['git', 'add', '.']);
        $this->executeCommandInRepoDir(['git', '-c', 'commit.gpgsign=false', 'commit', '-a', '-m', 'New CVE discovered']);
    }

    private function createAdvisoriesDatabaseRepo(): void
    {
        $this->filesystem->mkdir($this->repoDir);
        $this->executeCommandInRepoDir(['git', 'init']);
        $this->filesystem->mirror(
            __DIR__.'/../../../Resources/fixtures/security/security-advisories',
            $this->repoDir
        );
        $this->executeCommandInRepoDir(['git', 'add', '-A']);
        $this->executeCommandInRepoDir(['git', '-c', 'commit.gpgsign=false', 'commit', '-a', '-m', 'Add repo']);
    }

    /**
     * @param list<non-empty-string> $command
     */
    private function executeCommandInRepoDir(array $command): void
    {
        (new Process($command, $this->repoDir))->mustRun();
    }

    private function synchronizeAdvisoriesDatabase(): void
    {
        $this->filesystem->mirror(
            __DIR__.'/../../../Resources/fixtures/security/security-advisories',
            $this->dbDir
        );
    }

    private function insecureLock(): string
    {
        return (string) file_get_contents(__DIR__.'/../../../Resources/fixtures/security/locks/insecure-composer.lock');
    }

    private function safeLock(): string
    {
        return (string) file_get_contents(__DIR__.'/../../../Resources/fixtures/security/locks/safe-composer.lock');
    }
}
