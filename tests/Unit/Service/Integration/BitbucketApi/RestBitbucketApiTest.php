<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Integration\BitbucketApi;

use Bitbucket\Api\AbstractApi;
use Bitbucket\Api\CurrentUser;
use Bitbucket\Api\Repositories as RepositoriesApi;
use Bitbucket\Client;
use Bitbucket\ResultPagerInterface;
use Buddy\Repman\Service\Integration\BitbucketApi\Repositories;
use Buddy\Repman\Service\Integration\BitbucketApi\Repository;
use Buddy\Repman\Service\Integration\BitbucketApi\RestBitbucketApi;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RestBitbucketApiTest extends TestCase
{
    /**
     * @var MockObject|Client
     */
    private $clientMock;

    /**
     * @var MockObject|ResultPagerInterface
     */
    private $pagerMock;

    private RestBitbucketApi $api;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->clientMock->expects(self::once())->method('authenticate');
        $this->pagerMock = $this->createMock(ResultPagerInterface::class);

        $this->api = new RestBitbucketApi($this->clientMock, $this->pagerMock);
    }

    public function testReturnPrimaryEmail(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
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
        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('listEmails')->willReturn([]);
        $this->clientMock->method('currentUser')->willReturn($currentUser);

        $this->expectException(\RuntimeException::class);
        $this->api->primaryEmail('token');
    }

    /**
     * Every workspace has to be asked separately: the endpoint that answered for all of
     * them at once was withdrawn by Bitbucket (CHANGE-2770), so a user whose
     * repositories live in more than one workspace is the case that matters here.
     */
    public function testFetchRepositoriesFromEveryWorkspace(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $workspaceApis = [
            'repman' => $this->createMock(RepositoriesApi\Workspaces::class),
            'other-team' => $this->createMock(RepositoriesApi\Workspaces::class),
        ];

        $repos = $this->createMock(RepositoriesApi::class);
        $repos->method('workspaces')->willReturnCallback(
            fn (string $slug): RepositoriesApi\Workspaces => $workspaceApis[$slug]
        );
        $this->clientMock->method('currentUser')->willReturn($currentUser);
        $this->clientMock->method('repositories')->willReturn($repos);

        $this->pagerMock->method('fetchAll')->willReturnCallback(
            function (AbstractApi $api, string $method, array $parameters = []) use ($currentUser, $workspaceApis): array {
                if ($api === $currentUser) {
                    // unfiltered, or workspaces the user is only a member of drop out
                    self::assertSame([], $parameters);

                    return [['slug' => 'repman'], ['slug' => 'other-team']];
                }

                self::assertSame([['role' => 'member']], $parameters);

                if ($api === $workspaceApis['repman']) {
                    return [[
                        'uuid' => '099acebd-5158-459e-b05c-30e51b49a1a8',
                        'full_name' => 'repman/left-pad',
                        'links' => ['html' => ['href' => 'https://bitbucket.org/repman/left-pad']],
                    ]];
                }

                return [[
                    'uuid' => '74fb57b9-0820-4165-bba0-892eef8f69b8',
                    'full_name' => 'other-team/right-pad',
                    'links' => ['html' => ['href' => 'https://bitbucket.org/other-team/right-pad']],
                ]];
            }
        );

        self::assertEquals(new Repositories([
            new Repository('099acebd-5158-459e-b05c-30e51b49a1a8', 'repman/left-pad', 'https://bitbucket.org/repman/left-pad.git'),
            new Repository('74fb57b9-0820-4165-bba0-892eef8f69b8', 'other-team/right-pad', 'https://bitbucket.org/other-team/right-pad.git'),
        ]), $this->api->repositories('token'));
    }

    public function testFetchRepositoriesWhenUserBelongsToNoWorkspace(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $this->clientMock->method('currentUser')->willReturn($currentUser);
        // nothing to ask about, so the repositories endpoint is never reached
        $this->clientMock->expects(self::never())->method('repositories');
        $this->pagerMock->method('fetchAll')->willReturn([]);

        self::assertEquals(new Repositories([]), $this->api->repositories('token'));
    }

    public function testAddHookWhenNotExist(): void
    {
        $repos = $this->createMock(RepositoriesApi::class);
        $workspaces = $this->createMock(RepositoriesApi\Workspaces::class);
        $hooks = $this->createMock(RepositoriesApi\Workspaces\Hooks::class);
        $this->pagerMock->method('fetchAll')->willReturn([
            ['url' => 'https://bitbucket-pipelines.prod.public.atl-paas.net/rest/bitbucket/event/connect/onpush'],
        ]);
        $this->clientMock->method('repositories')->willReturn($repos);
        $repos->method('workspaces')->willReturn($workspaces);
        $workspaces->method('hooks')->willReturn($hooks);

        $hooks->expects(self::once())->method('create');

        $this->api->addHook('token', 'repman/left-pad', 'https://webhook.url');
    }

    public function testDoNotAddHookWhenExist(): void
    {
        $repos = $this->createMock(RepositoriesApi::class);
        $workspaces = $this->createMock(RepositoriesApi\Workspaces::class);
        $hooks = $this->createMock(RepositoriesApi\Workspaces\Hooks::class);
        $this->pagerMock->method('fetchAll')->willReturn([
            ['url' => 'https://bitbucket-pipelines.prod.public.atl-paas.net/rest/bitbucket/event/connect/onpush'],
            ['url' => 'https://webhook.url'],
        ]);
        $this->clientMock->method('repositories')->willReturn($repos);
        $repos->method('workspaces')->willReturn($workspaces);
        $workspaces->method('hooks')->willReturn($hooks);

        $hooks->expects(self::never())->method('create');

        $this->api->addHook('token', 'repman/left-pad', 'https://webhook.url');
    }

    public function testRemoveHookWhenExist(): void
    {
        $repos = $this->createMock(RepositoriesApi::class);
        $workspaces = $this->createMock(RepositoriesApi\Workspaces::class);
        $hooks = $this->createMock(RepositoriesApi\Workspaces\Hooks::class);
        $this->pagerMock->method('fetchAll')->willReturn([
            [
                'uuid' => '1d2c6ec8-1294-4471-b703-1d050f86bdd5',
                'url' => 'https://webhook.url',
            ],
        ]);
        $this->clientMock->method('repositories')->willReturn($repos);
        $repos->method('workspaces')->willReturn($workspaces);
        $workspaces->method('hooks')->willReturn($hooks);

        $hooks->expects(self::once())->method('remove')->with('1d2c6ec8-1294-4471-b703-1d050f86bdd5');

        $this->api->removeHook('token', 'repman/left-pad', 'https://webhook.url');
    }

    public function testRemoveHookWhenNotExist(): void
    {
        $repos = $this->createMock(RepositoriesApi::class);
        $workspaces = $this->createMock(RepositoriesApi\Workspaces::class);
        $hooks = $this->createMock(RepositoriesApi\Workspaces\Hooks::class);
        $this->pagerMock->method('fetchAll')->willReturn([
            [
                'uuid' => '1d2c6ec8-1294-4471-b703-1d050f86bdd5',
                'url' => 'https://other.url',
            ],
        ]);
        $this->clientMock->method('repositories')->willReturn($repos);
        $repos->method('workspaces')->willReturn($workspaces);
        $workspaces->method('hooks')->willReturn($hooks);

        $hooks->expects(self::never())->method('remove');

        $this->api->removeHook('token', 'repman/left-pad', 'https://webhook.url');
    }
}
