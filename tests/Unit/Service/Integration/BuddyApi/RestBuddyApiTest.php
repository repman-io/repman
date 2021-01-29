<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Integration\BuddyApi;

use Buddy\Apis\Emails;
use Buddy\Buddy;
use Buddy\BuddyResponse;
use Buddy\Repman\Service\Integration\BuddyApi\BuddyApiException;
use Buddy\Repman\Service\Integration\BuddyApi\RestBuddyApi;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RestBuddyApiTest extends TestCase
{
    /**
     * @var Buddy|MockObject
     */
    private $client;

    private RestBuddyApi $api;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Buddy::class);
        $this->api = new RestBuddyApi($this->client);
    }

    public function testThrowExceptionWhenPrimaryEmialNotFound(): void
    {
        $emails = $this->createMock(Emails::class);
        $emails->expects(self::once())->method('getAuthenticatedUserEmails')->with('some-token')->willReturn(new BuddyResponse(200, [], (string) json_encode([
            'emails' => [],
        ])));
        $this->client->method('getApiEmails')->willReturn($emails);

        $this->expectException(BuddyApiException::class);
        $this->api->primaryEmail('some-token');
    }

    public function testPrimaryEmail(): void
    {
        $emails = $this->createMock(Emails::class);
        $emails->expects(self::once())->method('getAuthenticatedUserEmails')->with('some-token')->willReturn(new BuddyResponse(200, [], (string) json_encode([
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
        ])));
        $this->client->method('getApiEmails')->willReturn($emails);

        self::assertEquals('admin@buddy.works', $this->api->primaryEmail('some-token'));
    }
}
