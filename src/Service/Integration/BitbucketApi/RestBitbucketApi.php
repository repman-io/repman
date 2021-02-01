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

    public function repositories(string $accessToken): Repositories
    {
        $this->client->authenticate(Client::AUTH_OAUTH_TOKEN, $accessToken);

        return new Repositories(array_map(function (array $repo): Repository {
            return new Repository(
                $repo['uuid'],
                $repo['full_name'],
                $repo['links']['html']['href'].'.git'
            );
        }, $this->pager->fetchAll($this->client->repositories(), 'list', [['role' => 'member']])));
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
