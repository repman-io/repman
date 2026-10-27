<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Security;

use Buddy\Repman\Service\Security\SecurityChecker\PackagistSecurityChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PackagistSecurityCheckerTest extends TestCase
{
    private const API = 'https://packagist.org/api/security-advisories/';

    private string $stateDir;

    protected function setUp(): void
    {
        $this->stateDir = sys_get_temp_dir().'/repman/security-advisories-state';
        (new Filesystem())->remove($this->stateDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->stateDir);
    }

    public function testInvalidLockFile(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid composer.lock');

        $this->checker(new MockHttpClient())->check('invalid');
    }

    public function testEmptyLockFileAsksNothing(): void
    {
        $client = new MockHttpClient(fn () => self::fail('no request expected'));

        self::assertSame([], $this->checker($client)->check('{}'));
    }

    public function testReportsTheAdvisoriesAffectingTheLockedVersions(): void
    {
        $requests = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [$method, $url, $options['body']];

            return new MockResponse((string) json_encode(['advisories' => [
                'league/commonmark' => [
                    ['advisoryId' => 'PKSA-1', 'title' => 'XSS in links', 'cve' => 'CVE-2025-1', 'link' => 'https://example.com/1', 'affectedVersions' => '>=2.0.0,<2.7.0'],
                    ['advisoryId' => 'PKSA-2', 'title' => 'Fixed long ago', 'cve' => null, 'link' => 'https://example.com/2', 'affectedVersions' => '<1.5.8'],
                ],
                'twig/twig' => [
                    ['advisoryId' => 'PKSA-3', 'title' => 'Sandbox escape', 'cve' => null, 'link' => null, 'affectedVersions' => '>=3.0.0,<3.11.2|>=1.0.0,<1.44.8'],
                ],
                'psr/log' => [],
            ]]));
        });

        $alerts = $this->checker($client)->check((string) json_encode([
            'packages' => [
                ['name' => 'league/commonmark', 'version' => '2.6.1'],
                ['name' => 'psr/log', 'version' => '3.0.2'],
                ['name' => 'acme/private', 'version' => 'dev-main'],
            ],
            'packages-dev' => [
                ['name' => 'twig/twig', 'version' => 'v3.10.0'],
            ],
        ]));

        self::assertSame([
            'league/commonmark' => ['version' => '2.6.1', 'advisories' => [
                ['title' => 'XSS in links', 'cve' => 'CVE-2025-1', 'link' => 'https://example.com/1'],
            ]],
            'twig/twig' => ['version' => 'v3.10.0', 'advisories' => [
                ['title' => 'Sandbox escape', 'cve' => '', 'link' => 'https://packagist.org/security-advisories/PKSA-3'],
            ]],
        ], $alerts);
        self::assertCount(1, $requests);
        self::assertSame('POST', $requests[0][0]);
        self::assertSame(self::API, $requests[0][1]);
        self::assertStringContainsString(urlencode('packages[0]').'=league%2Fcommonmark', $requests[0][2]);
    }

    public function testUpdateReportsNewAdvisoriesSinceTheLastCall(): void
    {
        $responses = [
            // the first call has nothing to compare with: everything gets scanned
            new MockResponse('{"advisories":[]}'),
            new MockResponse('{"advisories":{"twig/twig":[{"advisoryId":"PKSA-3"}]}}'),
        ];
        /** @var \ArrayObject<int, string> $urls */
        $urls = new \ArrayObject();
        $client = new MockHttpClient(function (string $method, string $url) use (&$responses, $urls): MockResponse {
            $urls[] = $method.' '.$url;

            return array_shift($responses) ?? self::fail('no more requests expected');
        });
        $checker = $this->checker($client);

        self::assertTrue($checker->update());
        self::assertCount(0, $urls);
        self::assertFileExists($this->stateDir.'/last-update');

        self::assertFalse($checker->update());
        self::assertTrue($checker->update());
        self::assertCount(2, $urls);
        self::assertMatchesRegularExpression('{^GET '.preg_quote(self::API).'\?updatedSince=\d+$}m', implode("\n", $urls->getArrayCopy()));
    }

    private function checker(MockHttpClient $client): PackagistSecurityChecker
    {
        return new PackagistSecurityChecker($client, self::API, $this->stateDir);
    }
}
