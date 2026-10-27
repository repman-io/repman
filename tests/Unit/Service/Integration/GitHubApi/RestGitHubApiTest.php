<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Integration\GitHubApi;

use Buddy\Repman\Service\Integration\GitHubApi\RestGitHubApi;
use Github\Api\CurrentUser;
use Github\Api\CurrentUser\Emails;
use Github\Api\CurrentUser\Memberships;
use Github\Api\Organization;
use Github\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RestGitHubApiTest extends TestCase
{
    /**
     * @var MockObject|Client
     */
    private $clientMock;

    private RestGitHubApi $api;

    protected function setUp(): void
    {
        $this->clientMock = $this->getMockBuilder(Client::class)
            // currentUser() and organization() are magic methods that end up in api()
            ->onlyMethods(['authenticate', 'getLastResponse', 'api'])
            ->getMock()
        ;
        $this->clientMock->expects($this->once())->method('authenticate');
        // mock pagination
        $this->clientMock->method('getLastResponse')->willReturn(new Response());

        $this->api = new RestGitHubApi($this->clientMock);
    }

    public function testReturnPrimaryEmail(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $emails = $this->createMock(Emails::class);

        $currentUser->method('emails')->willReturn($emails);
        $emails->method('all')->willReturn([
            [
                'email' => 'test@buddy.works',
                'verified' => true,
                'primary' => true,
                'visibility' => 'public',
            ], [
                'email' => 'octocat@github.com',
                'verified' => true,
                'primary' => false,
                'visibility' => 'public',
            ],
        ]);
        $this->clientMock->method('api')->with('currentUser')->willReturn($currentUser);

        self::assertSame('test@buddy.works', $this->api->primaryEmail('token'));
    }

    public function testThrowExceptionWhenPrimaryEmailNotFound(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $emails = $this->createMock(Emails::class);

        $currentUser->method('emails')->willReturn($emails);
        $emails->method('all')->willReturn([]);
        $this->clientMock->method('api')->with('currentUser')->willReturn($currentUser);

        $this->expectException(\RuntimeException::class);
        $this->api->primaryEmail('token');
    }

    public function testReturnRepository(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $memberships = $this->createMock(Memberships::class);
        $organization = $this->createMock(Organization::class);

        $organization->method('repositories')->willReturn([
            ['full_name' => 'buddy/repman'],
            ['full_name' => 'buddy/left-pad'],
        ]);

        $currentUser->method('memberships')->willReturn($memberships);
        $memberships->method('all')->willReturn([
            [
                'state' => 'active',
                'organization' => [
                    'login' => 'buddy',
                ],
            ],
            [
                'state' => 'pending',
                'organization' => [
                    'login' => 'invited-only',
                ],
            ],
        ]);
        $organization->expects(self::once())->method('repositories')->with('buddy');

        $currentUser->method('repositories')->willReturn([
            ['full_name' => 'private/repman'],
            ['full_name' => 'buddy/repman'],
        ]);

        $this->clientMock->method('api')->willReturnMap([['currentUser', $currentUser], ['organization', $organization]]);

        self::assertEquals([
            'private/repman',
            'buddy/repman',
            'buddy/left-pad',
        ], $this->api->repositories('token'));
    }
}
