<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\GitLabApi;

use Buddy\Repman\Service\GitLabApi;
use Gitlab\Client;

final class MattGitLabApi implements GitLabApi
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Project[]
     */
    public function projects(string $accessToken): array
    {
        $this->client->authenticate($accessToken, Client::AUTH_OAUTH_TOKEN);

        return array_map(function (array $project): Project {
            return new Project(
                $project['id'],
                $project['path_with_namespace'],
                $project['web_url']
            );
        }, $this->client->projects()->all([
            'simple' => true,
            'owned' => true,
            'membership' => true,
            'order_by' => 'path',
        ]));
    }

    public function addHook(string $accessToken, int $projectId, string $hookUrl): void
    {
        $this->client->authenticate($accessToken, Client::AUTH_OAUTH_TOKEN);

        foreach ($this->client->projects()->hooks($projectId) as $hook) {
            if ($hook['url'] === $hookUrl) {
                return;
            }
        }

        $this->client->projects()->addHook($projectId, $hookUrl, [
            'push_events' => true,
            'tag_push_events' => true,
        ]);
    }
}
