<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Integration\Aws;

use Aws\Credentials\Credentials;
use Buddy\Repman\Service\Integration\Aws\S3AdapterFactory;
use PHPUnit\Framework\TestCase;

class S3AdapterFactoryTest extends TestCase
{
    public function testCreateWithMachineCredentials(): void
    {
        $factory = new S3AdapterFactory('eu-east-1', false, '', '');

        $instance = $factory->create();
        $cfg = $instance->getConfig();

        self::assertSame('eu-east-1', $cfg['signing_region']);
        self::assertSame('s3v4', $cfg['signature_version']);
    }

    public function testCreateWithOpaqueCredentials(): void
    {
        $factory = new S3AdapterFactory('eu-east-1', true, 'mykey', 'secret');

        $instance = $factory->create();
        $cfg = $instance->getConfig();

        self::assertSame('eu-east-1', $cfg['signing_region']);
        self::assertSame('s3v4', $cfg['signature_version']);

        /** @var Credentials $creds */
        $creds = $instance->getCredentials()->wait();
        self::assertSame('mykey', $creds->getAccessKeyId());
        self::assertSame('secret', $creds->getSecretKey());
    }

    public function testCreateWithoutEndpoint(): void
    {
        $factory = new S3AdapterFactory('eu-east-1', true, 'mykey', 'secret');

        $instance = $factory->create();
        $endpoint = $instance->getEndpoint();

        self::assertSame($endpoint->getHost(), 's3.eu-east-1.amazonaws.com');
    }

    public function testCreateWithEndpoint(): void
    {
        $factory = new S3AdapterFactory(
            'eu-east-1',
            true,
            'mykey',
            'secret',
            'https://s3.example.com'
        );

        $instance = $factory->create();
        $endpoint = $instance->getEndpoint();

        self::assertSame($endpoint->getHost(), 's3.example.com');
    }

    /**
     * @return array<string, array<string, ?string>>
     */
    public function providesInvalidValueCombinationOfKeyAndSecret(): array
    {
        return [
            'when key is empty' => [
                '$key' => '',
                '$secret' => 'my_secret',
                '$expectedMessage' => 'Must pass AWS key when authentication is opaque',
            ],
            'when key is null' => [
                '$key' => null,
                '$secret' => 'my_secret',
                '$expectedMessage' => 'Must pass AWS key when authentication is opaque',
            ],
            'when secret is empty' => [
                '$key' => 'mykey',
                '$secret' => '',
                '$expectedMessage' => 'Must pass AWS secret when authentication is opaque',
            ],
            'when secret is null' => [
                '$key' => 'mykey',
                '$secret' => null,
                '$expectedMessage' => 'Must pass AWS secret when authentication is opaque',
            ],
            'when both are invalid' => [
                '$key' => '',
                '$secret' => '',
                '$expectedMessage' => 'Must pass AWS key when authentication is opaque',
            ],
        ];
    }

    /**
     * @dataProvider providesInvalidValueCombinationOfKeyAndSecret
     */
    public function testOpaqueWithoutCredentialsWillThrowError(
        ?string $key,
        ?string $secret,
        string $expectedMessage
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        new S3AdapterFactory('eu-east-1', true, $key, $secret);
    }
}
