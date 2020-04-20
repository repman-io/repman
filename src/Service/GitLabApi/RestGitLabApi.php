<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\GitLabApi;

use Buddy\Repman\Service\GitLabApi;
use Gitlab\Client;
use Gitlab\ResultPager;

final class RestGitLabApi implements GitLabApi
{
    private Client $client;
    private ResultPager $pager;

    public function __construct(Client $client, ResultPager $pager, ?string $url = null)
    {
        $this->client = $client;
        $this->pager = $pager;

        if ($url !== null) {
            $this->client->setUrl($url);
        }
    }

    public function projects(string $accessToken): Projects
    {
        $this->client->authenticate($accessToken, Client::AUTH_OAUTH_TOKEN);

        return new Projects(array_map(function (array $project): Project {
            return new Project(
                $project['id'],
                $project['path_with_namespace'],
                $project['web_url']
            );
        }, $this->pager->fetchAll($this->client->projects(), 'all', [[
            'simple' => true,
            'owned' => true,
            'membership' => true,
            'order_by' => 'path',
        ]])));
    }

    public function addHook(string $accessToken, int $projectId, string $hookUrl): void
    {
        $this->client->authenticate($accessToken, Client::AUTH_OAUTH_TOKEN);

        foreach ($this->pager->fetchAll($this->client->projects(), 'hooks', [$projectId]) as $hook) {
            if ($hook['url'] === $hookUrl) {
                return;
            }
        }

        $this->client->projects()->addHook($projectId, $hookUrl, [
            'push_events' => true,
            'tag_push_events' => true,
        ]);
    }

    public function removeHook(string $accessToken, int $projectId, string $hookUrl): void
    {
        $this->client->authenticate($accessToken, Client::AUTH_OAUTH_TOKEN);

        foreach ($this->pager->fetchAll($this->client->projects(), 'hooks', [$projectId]) as $hook) {
            if ($hook['url'] === $hookUrl) {
                $this->client->projects()->removeHook($projectId, $hook['id']);
            }
        }
    }
}
