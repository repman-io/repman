<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Telemetry;

use Buddy\Repman\Service\Telemetry\Entry;
use Buddy\Repman\Service\Telemetry\TelemetryEndpoint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class TelemetryEndpointTest extends TestCase
{
    public function testSuccessfulRequest(): void
    {
        $called = 0;
        $client = new MockHttpClient(function ($method, $url, $options) use (&$called): MockResponse {
            ++$called;

            return new MockResponse();
        });
        $endpoint = new TelemetryEndpoint($client);
        $endpoint->send($this->entry());

        self::assertEquals(1, $called);
    }

    public function testFailedRequest(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error while sending telemetry data. HTTP error: 500');

        $endpoint = new TelemetryEndpoint(
            new MockHttpClient([new MockResponse('', ['http_code' => 500])])
        );
        $endpoint->send($this->entry());
    }

    private function entry(): Entry
    {
        return new Entry(
            new \DateTimeImmutable('2020-02-01 00:00:00'),
            'instance-id',
            '0.4.0',
            'Linux',
            '7.4.5',
            1,
            2,
            3,
            4,
            5,
            6,
        );
    }
}
