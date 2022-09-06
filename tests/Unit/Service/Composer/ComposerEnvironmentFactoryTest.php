<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Composer;

use Buddy\Repman\Service\Composer\ComposerEnvironmentFactory;
use PHPUnit\Framework\TestCase;

final class ComposerEnvironmentFactoryTest extends TestCase
{
    /**
     * @dataProvider composerUserAgentDataProvider
     */
    public function testCreationByUserAgent(
        string $userAgent,
        string $version
    ): void {
        $composerInfo = (new ComposerEnvironmentFactory())->fromUserAgent($userAgent);
        $this->assertEquals($version, $composerInfo->getVersion());
    }

    public function composerUserAgentDataProvider(): iterable
    {
        $userAgents = [
            'Composer/2.4.0-RC1 (Darwin; 21.6.0; PHP 8.1.10; cURL 7.85.0; Platform-PHP 7.4.25)' => [
                'version' => '2.4.0-RC1',
            ],
            'Composer/2.4.0-RC1 (Darwin; 21.6.0; PHP 7.4.30; cURL 7.85.0; Platform-PHP 7.4.25)' => [
                'version' => '2.4.0-RC1',
            ],
            'Composer/2.1.9 (Linux; 4.19.0-17-amd64; PHP 7.4.24; cURL 7.64.0; Platform-PHP 7.1.3; CI)' => [
                'version' => '2.1.9',
            ],
            'Composer/1.10.1 (Windows NT; 10.0; PHP 7.4.0)' => [
                'version' => '1.10.1',
            ],
            'Composer/2.2.5 (; PHP 8.1.3; cURL 7.64.0)' => [
                'version' => '2.2.5',
            ],
            'Composer/2.4.1 (Linux; 4.19.0-17-amd64; PHP 8.1.8; cURL 7.64.0; Platform-PHP 8.1.1; CI)' => [
                'version' => '2.4.1',
            ],
            'Composer/2.4.1 (Linux; 4.15.0-187-generic; PHP 8.1.8; cURL 7.64.0; Platform-PHP 8.1.1; CI)' => [
                'version' => '2.4.1',
            ],
            'Composer/2.4.1 (Linux; 4.19.0-18-amd64; PHP 8.1.8; cURL 7.64.0; Platform-PHP 8.1.1; CI)' => [
                'version' => '2.4.1',
            ],
        ];

        foreach ($userAgents as $userAgent => $data) {
            yield $userAgent => array_merge(['userAgent' => $userAgent], $data);
        }
    }
}
