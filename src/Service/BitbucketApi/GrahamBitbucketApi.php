<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\BitbucketApi;

use Bitbucket\Client;
use Buddy\Repman\Service\BitbucketApi;

final class GrahamBitbucketApi implements BitbucketApi
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
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

        // TODO: handle pagination
        return new Repositories(array_map(function (array $repo): Repository {
            return new Repository(
                $repo['uuid'],
                $repo['full_name'],
                $repo['links']['html']['href'].'.git'
            );
        }, $this->client->repositories()->list([
            'role' => 'member',
            'pagelen' => 100,
        ])['values'] ?? []));
    }

    public function addHook(string $accessToken, string $fullName, string $hookUrl): void
    {
        $this->client->authenticate(Client::AUTH_OAUTH_TOKEN, $accessToken);
        [$username, $repo] = explode('/', $fullName);

        $hooks = $this->client->repositories()->users($username)->hooks($repo);

        // TODO: handle pagination
        foreach ($hooks->list(['pagelen' => 100])['values'] ?? [] as $hook) {
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
        [$username, $repo] = explode('/', $fullName);

        $hooks = $this->client->repositories()->users($username)->hooks($repo);

        // TODO: handle pagination
        foreach ($hooks->list(['pagelen' => 100])['values'] ?? [] as $hook) {
            if ($hook['url'] === $hookUrl) {
                $hooks->remove($hook['uuid']);
            }
        }
    }
}
