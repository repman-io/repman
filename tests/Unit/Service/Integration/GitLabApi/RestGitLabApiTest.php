<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Integration\GitLabApi;

use Buddy\Repman\Service\Integration\GitLabApi\Project;
use Buddy\Repman\Service\Integration\GitLabApi\Projects;
use Buddy\Repman\Service\Integration\GitLabApi\RestGitLabApi;
use Gitlab\Api\Projects as ProjectsApi;
use Gitlab\Client;
use Gitlab\ResultPager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RestGitLabApiTest extends TestCase
{
    /**
     * @var MockObject|Client
     */
    private $clientMock;

    private RestGitLabApi $api;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->clientMock->expects(self::once())->method('authenticate');

        $this->api = new RestGitLabApi($this->clientMock, new ResultPager($this->clientMock), 'https://gitlab.com');
    }

    public function testFetchUserProjects(): void
    {
        $projects = $this->createMock(ProjectsApi::class);
        $projects->method('all')->willReturn([
            [
                'id' => 17275574,
                'path' => 'left-pad',
                'path_with_namespace' => 'repman/left-pad',
                'created_at' => '2020-03-04T08:06:05.204Z',
                'default_branch' => 'master',
                'ssh_url_to_repo' => 'git@gitlab.com:repman/left-pad.git',
                'http_url_to_repo' => 'https://gitlab.com/repman/left-pad.git',
                'web_url' => 'https://gitlab.com/repman/left-pad',
            ],
            [
                'id' => 17275573,
                'path' => 'right-pad',
                'path_with_namespace' => 'repman/right-pad',
                'created_at' => '2020-03-04T08:06:05.204Z',
                'default_branch' => 'master',
                'ssh_url_to_repo' => 'git@gitlab.com:repman/right-pad.git',
                'http_url_to_repo' => 'https://gitlab.com/repman/right-pad.git',
                'web_url' => 'https://gitlab.com/repman/right-pad',
            ],
        ]);
        $this->clientMock->method('projects')->willReturn($projects);

        self::assertEquals(new Projects([
            new Project(17275574, 'repman/left-pad', 'https://gitlab.com/repman/left-pad'),
            new Project(17275573, 'repman/right-pad', 'https://gitlab.com/repman/right-pad'),
        ]), $this->api->projects('gitlab-token'));
    }

    public function testAddHookWhenNotExist(): void
    {
        $projects = $this->createMock(ProjectsApi::class);
        $projects->expects(self::once())->method('addHook');
        $projects->method('hooks')->willReturn([[
            'id' => 1834838,
            'url' => 'https://repman.wip/hook',
            'created_at' => '2020-03-04T10:26:45.746Z',
            'push_events' => true,
        ]]);
        $this->clientMock->method('projects')->willReturn($projects);

        $this->api->addHook('token', 123, 'https://webhook.url');
    }

    public function testDoNotAddHookWhenExist(): void
    {
        $projects = $this->createMock(ProjectsApi::class);
        $projects->expects(self::never())->method('addHook');
        $projects->method('hooks')->willReturn([[
            'id' => 1834838,
            'url' => 'https://webhook.url',
            'created_at' => '2020-03-04T10:26:45.746Z',
            'push_events' => true,
        ]]);
        $this->clientMock->method('projects')->willReturn($projects);

        $this->api->addHook('token', 123, 'https://webhook.url');
    }

    public function testRemoveHookWhenExist(): void
    {
        $projects = $this->createMock(ProjectsApi::class);
        $projects->expects(self::once())->method('removeHook')->with(123, 1834838);
        $projects->method('hooks')->willReturn([[
            'id' => 1834838,
            'url' => 'https://webhook.url',
            'created_at' => '2020-03-04T10:26:45.746Z',
            'push_events' => true,
        ]]);
        $this->clientMock->method('projects')->willReturn($projects);

        $this->api->removeHook('token', 123, 'https://webhook.url');
    }

    public function testRemoveHookWhenNotExist(): void
    {
        $projects = $this->createMock(ProjectsApi::class);
        $projects->expects(self::never())->method('removeHook');
        $projects->method('hooks')->willReturn([[
            'id' => 1834838,
            'url' => 'https://other.url',
            'created_at' => '2020-03-04T10:26:45.746Z',
            'push_events' => true,
        ]]);
        $this->clientMock->method('projects')->willReturn($projects);

        $this->api->removeHook('token', 123, 'https://webhook.url');
    }
}
