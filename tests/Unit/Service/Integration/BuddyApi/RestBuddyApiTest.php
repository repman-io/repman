<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Integration\BuddyApi;

use Buddy\Repman\Service\Integration\BuddyApi\BuddyApiException;
use Buddy\Repman\Service\Integration\BuddyApi\RestBuddyApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class RestBuddyApiTest extends TestCase
{
    public function testThrowExceptionWhenPrimaryEmialNotFound(): void
    {
        $api = new RestBuddyApi(new MockHttpClient(new MockResponse((string) json_encode(['emails' => []]))));

        $this->expectException(BuddyApiException::class);
        $api->primaryEmail('some-token');
    }

    public function testPrimaryEmail(): void
    {
        $requests = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [$method, $url, $options['normalized_headers']['authorization'][0] ?? null];

            return new MockResponse((string) json_encode([
                'emails' => [
                    [
                        'email' => 'some@buddy.works',
                        'confirmed' => false,
                    ],
                    [
                        'email' => 'admin@buddy.works',
                        'confirmed' => true,
                    ],
                ],
            ]));
        });

        self::assertSame('admin@buddy.works', (new RestBuddyApi($client))->primaryEmail('some-token'));
        self::assertEquals([['GET', 'https://api.buddy.works/user/emails', 'Authorization: Bearer some-token']], $requests);
    }

    public function testThrowExceptionOnHttpError(): void
    {
        $api = new RestBuddyApi(new MockHttpClient(new MockResponse('{"errors":[]}', ['http_code' => 401])));

        $this->expectException(BuddyApiException::class);
        $api->primaryEmail('expired-token');
    }

    public function testThrowExceptionOnInvalidJson(): void
    {
        $api = new RestBuddyApi(new MockHttpClient(new MockResponse('not json')));

        $this->expectException(BuddyApiException::class);
        $api->primaryEmail('some-token');
    }
}
