<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Telemetry;

use Buddy\Repman\Service\Telemetry\Entry;
use Buddy\Repman\Service\Telemetry\Entry\Downloads;
use Buddy\Repman\Service\Telemetry\Entry\Instance;
use Buddy\Repman\Service\Telemetry\Entry\Organization;
use Buddy\Repman\Service\Telemetry\Entry\Package;
use Buddy\Repman\Service\Telemetry\Entry\Proxy;
use Buddy\Repman\Service\Telemetry\TechnicalEmail;
use Buddy\Repman\Service\Telemetry\TelemetryEndpoint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class TelemetryEndpointTest extends TestCase
{
    public function testSuccessfulSend(): void
    {
        $called = 0;
        $response = [];
        $client = new MockHttpClient(function ($method, $url, $options) use (&$called, &$response): MockResponse {
            ++$called;
            $response = $options['body'];

            return new MockResponse();
        });
        $endpoint = new TelemetryEndpoint($client);
        $endpoint->send($this->entry());

        self::assertEquals(1, $called);
        self::assertJsonStringEqualsJsonString(
            $response,
            '
            {
                "id": "20200721_8f43446b-52a3-4bd9-9a8a-ecc955ac754d",
                "date": "2020-07-21",
                "instance": {
                    "id": "8f43446b-52a3-4bd9-9a8a-ecc955ac754d",
                    "version": "0.5.0",
                    "osVersion": "Linux 5.3.0-62-generic",
                    "phpVersion": "7.4.5",
                    "users": 3,
                    "config": {
                        "local_authentication": "login_and_registration",
                        "oauth_registration": "enabled",
                        "telemetry": "enabled"
                    },
                    "failedMessages": 0
                },
                "organizations": [
                    {
                        "packages": [
                            {
                                "type": "github-oauth",
                                "lastRelease": "2018-01-23T21:31:10+00:00",
                                "lastSync": "2020-07-22T13:37:56+00:00",
                                "lastScan": "2020-07-22T13:37:56+00:00",
                                "hasError": false,
                                "hasWebhook": true,
                                "scanStatus": "ok",
                                "downloads": 1,
                                "webhookRequests": 1
                            }
                        ],
                        "tokens": 1,
                        "public": true,
                        "members": 2,
                        "owners": 1
                    }
                ],
                "downloads": {
                    "proxy": 1,
                    "private": 1
                },
                "proxy": {
                    "packages": 1
                }
            }
            '
        );
    }

    public function testFailedSend(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error while sending telemetry data. HTTP error: 500');

        $endpoint = new TelemetryEndpoint(
            new MockHttpClient([new MockResponse('', ['http_code' => 500])])
        );
        $endpoint->send($this->entry());
    }

    public function testSuccessfulAddTechnicalEmail(): void
    {
        $called = 0;
        $response = [];
        $client = new MockHttpClient(function ($method, $url, $options) use (&$called, &$response): MockResponse {
            ++$called;
            $response = $options['body'];

            return new MockResponse();
        });
        $endpoint = new TelemetryEndpoint($client);
        $endpoint->addTechnicalEmail($this->email());

        self::assertEquals(1, $called);
        self::assertJsonStringEqualsJsonString(
            $response,
            '
            {
                "instanceId": "8f43446b-52a3-4bd9-9a8a-ecc955ac754d",
                "email": "john.doe@example.com"
            }
            '
        );
    }

    public function testFailedAddTechnicalEmailAddress(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error while sending telemetry data. HTTP error: 403');

        $endpoint = new TelemetryEndpoint(
            new MockHttpClient([new MockResponse('', ['http_code' => 403])])
        );
        $endpoint->addTechnicalEmail($this->email());
    }

    public function testSuccessfulRemoveTechnicalEmail(): void
    {
        $called = 0;
        $response = [];
        $client = new MockHttpClient(function ($method, $url, $options) use (&$called, &$response): MockResponse {
            ++$called;
            $response = $options['body'];

            return new MockResponse();
        });
        $endpoint = new TelemetryEndpoint($client);
        $endpoint->removeTechnicalEmail($this->email());

        self::assertEquals(1, $called);
        self::assertJsonStringEqualsJsonString(
            $response,
            '
            {
                "instanceId": "8f43446b-52a3-4bd9-9a8a-ecc955ac754d",
                "email": "john.doe@example.com"
            }
            '
        );
    }

    public function testFailedRemoveTechnicalEmailAddress(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error while sending telemetry data. HTTP error: 403');

        $endpoint = new TelemetryEndpoint(
            new MockHttpClient([new MockResponse('', ['http_code' => 403])])
        );
        $endpoint->removeTechnicalEmail($this->email());
    }

    private function entry(): Entry
    {
        $organization = new Organization(
            'f6f501b9-b57e-4773-b817-837197b44cb0',
            1,
            true,
            2,
            1
        );
        $organization->addPackages([
            new Package(
                'github-oauth',
                new \DateTimeImmutable('2018-01-23 21:31:10'),
                new \DateTimeImmutable('2020-07-22 13:37:56'),
                new \DateTimeImmutable('2020-07-22 13:37:56'),
                false,
                true,
                'ok',
                1,
                1
            ),
        ]);

        return new Entry(
            new \DateTimeImmutable('2020-07-21 12:13:13'),
            new Instance(
                '8f43446b-52a3-4bd9-9a8a-ecc955ac754d',
                '0.5.0',
                'Linux 5.3.0-62-generic',
                '7.4.5',
                3,
                0,
                [
                    'local_authentication' => 'login_and_registration',
                    'oauth_registration' => 'enabled',
                    'telemetry' => 'enabled',
                ],
            ),
            [$organization],
            new Downloads(1, 1),
            new Proxy(1)
        );
    }

    private function email(): TechnicalEmail
    {
        return new TechnicalEmail('john.doe@example.com', '8f43446b-52a3-4bd9-9a8a-ecc955ac754d');
    }
}
