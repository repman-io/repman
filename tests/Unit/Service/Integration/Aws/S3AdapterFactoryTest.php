<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Integration\Aws;

use Aws\Credentials\Credentials;
use Buddy\Repman\Service\Integration\Aws\S3AdapterFactory;
use PHPUnit\Framework\TestCase;

class S3AdapterFactoryTest extends TestCase
{
    public function testCreateWithDefaultCredentialChain(): void
    {
        $factory = new S3AdapterFactory('eu-east-1', false, '', '');

        $instance = $factory->create();
        $cfg = $instance->getConfig();

        self::assertSame('eu-east-1', $cfg['signing_region']);
        self::assertSame('s3v4', $cfg['signature_version']);
        // The credentials provider is set - getCredentials() returns a promise
        self::assertInstanceOf(\GuzzleHttp\Promise\PromiseInterface::class, $instance->getCredentials());
    }

    public function testCreateWithDefaultCredentialChainUsesProvider(): void
    {
        $factory = new S3AdapterFactory('eu-east-1', false);

        $instance = $factory->create();

        // The credentials provider is set - getCredentials() returns a promise
        // When using the default provider chain, the SDK handles credential resolution
        self::assertInstanceOf(\GuzzleHttp\Promise\PromiseInterface::class, $instance->getCredentials());
    }

    /**
     * Verifies that when isOpaqueAuth=false, the credential chain is used
     * and static credentials are NOT embedded in the client config.
     */
    public function testCredentialChainIsUsedWhenOpaqueAuthIsFalse(): void
    {
        // Even if key/secret are provided, they should be ignored when isOpaqueAuth=false
        $factory = new S3AdapterFactory('eu-west-1', false, 'ignored-key', 'ignored-secret');

        $instance = $factory->create();
        $cfg = $instance->getConfig();

        // The 'credentials' key should NOT contain static credentials array
        // Instead, the SDK uses the credential provider chain internally
        self::assertArrayNotHasKey('credentials', $cfg);

        // Verify the client is configured correctly
        self::assertSame('eu-west-1', $cfg['signing_region']);

        // getCredentials() should return a promise (from the provider chain)
        $credentialsPromise = $instance->getCredentials();
        self::assertInstanceOf(\GuzzleHttp\Promise\PromiseInterface::class, $credentialsPromise);
    }

    /**
     * Verifies that when isOpaqueAuth=true, static credentials are used
     * and the credential chain is NOT used.
     */
    public function testStaticCredentialsAreUsedWhenOpaqueAuthIsTrue(): void
    {
        $factory = new S3AdapterFactory('eu-west-1', true, 'my-access-key', 'my-secret-key');

        $instance = $factory->create();

        // When using opaque auth, credentials should resolve to the provided values
        /** @var Credentials $creds */
        $creds = $instance->getCredentials()->wait();

        self::assertInstanceOf(Credentials::class, $creds);
        self::assertSame('my-access-key', $creds->getAccessKeyId());
        self::assertSame('my-secret-key', $creds->getSecretKey());
    }

    /**
     * Verifies credential chain works with environment variables when available.
     * This test sets temporary env vars to simulate AWS credential environment.
     */
    public function testCredentialChainResolvesFromEnvironmentVariables(): void
    {
        // Store original values
        $originalKey = getenv('AWS_ACCESS_KEY_ID');
        $originalSecret = getenv('AWS_SECRET_ACCESS_KEY');

        try {
            // Set test credentials in environment
            putenv('AWS_ACCESS_KEY_ID=env-test-key');
            putenv('AWS_SECRET_ACCESS_KEY=env-test-secret');

            $factory = new S3AdapterFactory('eu-west-1', false);
            $instance = $factory->create();

            // The credential chain should resolve from environment variables
            /** @var Credentials $creds */
            $creds = $instance->getCredentials()->wait();

            self::assertInstanceOf(Credentials::class, $creds);
            self::assertSame('env-test-key', $creds->getAccessKeyId());
            self::assertSame('env-test-secret', $creds->getSecretKey());
        } finally {
            // Restore original environment
            if ($originalKey === false) {
                putenv('AWS_ACCESS_KEY_ID');
            } else {
                putenv("AWS_ACCESS_KEY_ID=$originalKey");
            }
            if ($originalSecret === false) {
                putenv('AWS_SECRET_ACCESS_KEY');
            } else {
                putenv("AWS_SECRET_ACCESS_KEY=$originalSecret");
            }
        }
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

        self::assertSame('s3.example.com', $endpoint->getHost());
    }

    public function testCreateWithPathStyleEndpoints(): void
    {
        $factory = new S3AdapterFactory(
            'eu-east-1',
            true,
            'mykey',
            'secret',
            'https://s3.example.com',
            true
        );

        $instance = $factory->create();
        self::assertTrue($instance->getConfig('use_path_style_endpoint'));
    }

    public function testExpectDefaultPathStyleOptionToBeFalse(): void
    {
        $factory = new S3AdapterFactory(
            'eu-east-1',
            true,
            'mykey',
            'secret',
            'https://s3.example.com',
        );

        $instance = $factory->create();
        self::assertFalse($instance->getConfig('use_path_style_endpoint'));
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
