<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\BitbucketApi;

use Bitbucket\Api\CurrentUser;
use Bitbucket\Client;
use Buddy\Repman\Service\BitbucketApi\GrahamBitbucketApi;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GrahamBitbucketApiTest extends TestCase
{
    /**
     * @var MockObject|Client
     */
    private $clientMock;

    private GrahamBitbucketApi $api;

    protected function setUp(): void
    {
        $this->clientMock = $this->getMockBuilder(Client::class)->getMock();
        $this->clientMock->expects($this->once())->method('authenticate');

        $this->api = new GrahamBitbucketApi($this->clientMock);
    }

    public function testReturnPrimaryEmail(): void
    {
        $currentUser = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->getMock();
        $currentUser->method('listEmails')->willReturn([
          'pagelen' => 10,
          'values' => [
            [
                'is_primary' => false,
                'is_confirmed' => false,
                'type' => 'email',
                'email' => 'admin.of@the.world',
                'links' => [],
            ],
            [
                'is_primary' => true,
                'is_confirmed' => true,
                'type' => 'email',
                'email' => 'test@buddy.works',
                'links' => [],
            ],
          ],
          'page' => 1,
          'size' => 2,
        ]);
        $this->clientMock->method('currentUser')->willReturn($currentUser);

        self::assertEquals('test@buddy.works', $this->api->primaryEmail('token'));
    }

    public function testThrowExceptionWhenPrimaryEmailNotFound(): void
    {
        $currentUser = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->getMock();
        $currentUser->method('listEmails')->willReturn([]);
        $this->clientMock->method('currentUser')->willReturn($currentUser);

        $this->expectException(\RuntimeException::class);
        $this->api->primaryEmail('token');
    }
}
