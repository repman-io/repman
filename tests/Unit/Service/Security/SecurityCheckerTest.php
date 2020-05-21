<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Security;

use Buddy\Repman\Service\Security\SecurityChecker;
use PHPUnit\Framework\TestCase;

final class SecurityCheckerTest extends TestCase
{
    private SecurityChecker $checker;
    private string $dbDir;
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->dbDir = __DIR__.'/../../../Resources/fixtures/security/security-advisories';
        $this->fixturesDir = __DIR__.'/../../../Resources/fixtures/security/locks';

        $this->checker = new SecurityChecker($this->dbDir);
    }

    public function testInvalidLockFile(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid composer.lock');

        $this->checker->check('invalid');
    }

    public function testMissingDatabase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Advisories database not found');

        $this->checker = new SecurityChecker(sys_get_temp_dir().'/bogus-security-advisories');
        $this->checker->check($this->insecureLock());
    }

    public function testEmptyLockFile(): void
    {
        self::assertEquals($this->checker->check('{}'), []);
    }

    public function testSuccessfulScanWithAlerts(): void
    {
        self::assertEquals($this->checker->check($this->insecureLock()), [
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
        self::assertEquals($this->checker->check($this->safeLock()), []);
    }

    private function insecureLock(): string
    {
        return (string) file_get_contents($this->fixturesDir.'/insecure-composer.lock');
    }

    private function safeLock(): string
    {
        return (string) file_get_contents($this->fixturesDir.'/safe-composer.lock');
    }
}
