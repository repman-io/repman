<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\BitbucketApi;

use Bitbucket\Client;
use Bitbucket\ResultPagerInterface;
use Buddy\Repman\Service\Integration\BitbucketApi;

final class RestBitbucketApi implements BitbucketApi
{
    private Client $client;
    private ResultPagerInterface $pager;

    public function __construct(Client $client, ResultPagerInterface $pager)
    {
        $this->client = $client;
        $this->pager = $pager;
    }

    public function primaryEmail(string $accessToken): string
    {
        $this->client->authenticate(Client::AUTH_OAUTH_TOKEN, $accessToken);
        foreach ($this->client->currentUser()->listEmails()['values'] ?? [] as $email) {
            if ($email['is_primary'] === true && $email['is_confirmed']) {
                return $email['email'];
            }
        }

        throw new \RuntimeException('Primary e-mail not found.');
    }

    /**
     * Assembled one workspace at a time because Bitbucket removed the endpoint that
     * listed every repository a user can reach in a single call - GET /2.0/repositories,
     * sunset on 2026-04-14 as part of CHANGE-2770 - and shipped no replacement for it.
     * Until then this was one request; it is now one per workspace the user belongs to.
     */
    public function repositories(string $accessToken): Repositories
    {
        $this->client->authenticate(Client::AUTH_OAUTH_TOKEN, $accessToken);

        $repositories = [];
        foreach ($this->workspaceSlugs() as $slug) {
            $repositories = array_merge($repositories, array_map(function (array $repo): Repository {
                return new Repository(
                    $repo['uuid'],
                    $repo['full_name'],
                    $repo['links']['html']['href'].'.git'
                );
            }, $this->pager->fetchAll($this->client->repositories()->workspaces($slug), 'list', [['role' => 'member']])));
        }

        return new Repositories($repositories);
    }

    /**
     * GET /2.0/workspaces, which survived the change above - it is a different endpoint
     * from the GET /2.0/user/permissions/workspaces that was withdrawn alongside it.
     * Deliberately unfiltered: its default is every workspace the user belongs to, and
     * narrowing by role here would drop repositories that used to be listed.
     *
     * @return string[]
     */
    private function workspaceSlugs(): array
    {
        return array_map(
            function (array $workspace): string {
                return $workspace['slug'];
            },
            $this->pager->fetchAll($this->client->currentUser(), 'listWorkspaces')
        );
    }

    public function addHook(string $accessToken, string $fullName, string $hookUrl): void
    {
        $this->client->authenticate(Client::AUTH_OAUTH_TOKEN, $accessToken);
        [$workspace, $repo] = explode('/', $fullName);
        $hooks = $this->client->repositories()->workspaces($workspace)->hooks($repo);

        foreach ($this->pager->fetchAll($hooks, 'list') as $hook) {
            if ($hook['url'] === $hookUrl) {
                return;
            }
        }

        $hooks->create([
            'description' => 'Repman repository update',
            'url' => $hookUrl,
            'active' => true,
            'events' => ['repo:push'],
        ]);
    }

    public function removeHook(string $accessToken, string $fullName, string $hookUrl): void
    {
        $this->client->authenticate(Client::AUTH_OAUTH_TOKEN, $accessToken);
        [$workspace, $repo] = explode('/', $fullName);

        $hooks = $this->client->repositories()->workspaces($workspace)->hooks($repo);

        foreach ($this->pager->fetchAll($hooks, 'list') as $hook) {
            if ($hook['url'] === $hookUrl) {
                $hooks->remove($hook['uuid']);
            }
        }
    }
}
