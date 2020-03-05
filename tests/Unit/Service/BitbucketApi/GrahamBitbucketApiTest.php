<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\BitbucketApi;

use Bitbucket\Api\CurrentUser;
use Bitbucket\Api\Repositories;
use Bitbucket\Client;
use Buddy\Repman\Service\BitbucketApi\GrahamBitbucketApi;
use Buddy\Repman\Service\BitbucketApi\Repository;
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

    public function testFetchRepositories(): void
    {
        $repos = $this->getMockBuilder(Repositories::class)->disableOriginalConstructor()->getMock();
        $repos->method('list')->willReturn([
            [
                'full_name' => 'repman/left-pad',
                'links' => ['self' => 'https://gitlab.com/repman/left-pad'],
            ],
            [
                'full_name' => 'repman/right-pad',
                'links' => ['self' => 'https://gitlab.com/repman/right-pad'],
            ],
        ]);
        $this->clientMock->method('repositories')->willReturn($repos);

        self::assertEquals([
            new Repository('repman/left-pad', 'https://gitlab.com/repman/left-pad'),
            new Repository('repman/right-pad', 'https://gitlab.com/repman/right-pad'),
        ], $this->api->repositories('token'));
    }

    public function testAddHookWhenNotExist(): void
    {
        $repos = $this->getMockBuilder(Repositories::class)->disableOriginalConstructor()->getMock();
        $users = $this->getMockBuilder(Repositories\Users::class)->disableOriginalConstructor()->getMock();
        $hooks = $this->getMockBuilder(Repositories\Users\Hooks::class)->disableOriginalConstructor()->getMock();
        $hooks->method('list')->willReturn([
            'values' => [
                ['url' => 'https://bitbucket-pipelines.prod.public.atl-paas.net/rest/bitbucket/event/connect/onpush'],
            ],
        ]);
        $this->clientMock->method('repositories')->willReturn($repos);
        $repos->method('users')->willReturn($users);
        $users->method('hooks')->willReturn($hooks);

        $hooks->expects($this->once())->method('create');

        $this->api->addHook('token', 'repman/left-pad', 'https://webhook.url');
    }

    public function testDoNotAddHookWhenExist(): void
    {
        $repos = $this->getMockBuilder(Repositories::class)->disableOriginalConstructor()->getMock();
        $users = $this->getMockBuilder(Repositories\Users::class)->disableOriginalConstructor()->getMock();
        $hooks = $this->getMockBuilder(Repositories\Users\Hooks::class)->disableOriginalConstructor()->getMock();
        $hooks->method('list')->willReturn([
            'values' => [
                ['url' => 'https://bitbucket-pipelines.prod.public.atl-paas.net/rest/bitbucket/event/connect/onpush'],
                ['url' => 'https://webhook.url'],
            ],
        ]);
        $this->clientMock->method('repositories')->willReturn($repos);
        $repos->method('users')->willReturn($users);
        $users->method('hooks')->willReturn($hooks);

        $hooks->expects($this->never())->method('create');

        $this->api->addHook('token', 'repman/left-pad', 'https://webhook.url');
    }
}
