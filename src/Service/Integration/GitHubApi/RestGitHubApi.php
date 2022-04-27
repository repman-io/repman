<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\GitHubApi;

use Buddy\Repman\Service\Integration\GitHubApi;
use Github\AuthMethod;
use Github\Client;
use Github\ResultPager;

final class RestGitHubApi implements GitHubApi
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function primaryEmail(string $accessToken): string
    {
        $this->client->authenticate($accessToken, null, AuthMethod::JWT);
        foreach ($this->client->currentUser()->emails()->all() as $email) {
            if ($email['primary'] === true) {
                return $email['email'];
            }
        }

        throw new \RuntimeException('Primary e-mail not found.');
    }

    /**
     * @return array<int,string>
     */
    public function repositories(string $accessToken): array
    {
        $this->client->authenticate($accessToken, null, AuthMethod::JWT);

        $repos = array_map(fn ($repo) => $repo['full_name'], $this->privateRepos());

        foreach ($this->memberships() as $membership) {
            foreach ($this->organizationRepos($membership['organization']['login']) as $repo) {
                $repos[] = $repo['full_name'];
            }
        }

        return $repos;
    }

    /**
     * @codeCoverageIgnore
     */
    public function addHook(string $accessToken, string $repo, string $url): void
    {
        [$owner, $repo] = explode('/', $repo);
        $this->client->authenticate($accessToken, null, AuthMethod::JWT);

        foreach ($this->client->repositories()->hooks()->all($owner, $repo) as $hook) {
            if ($hook['config']['url'] === $url) {
                return;
            }
        }

        $this->client->repositories()->hooks()->create($owner, $repo, [
            'name' => 'web',
            'config' => [
                'url' => $url,
                'content_type' => 'json',
            ],
        ]);
    }

    /**
     * @codeCoverageIgnore
     */
    public function removeHook(string $accessToken, string $repo, string $url): void
    {
        [$owner, $repo] = explode('/', $repo);
        $this->client->authenticate($accessToken, null, AuthMethod::JWT);

        foreach ($this->client->repositories()->hooks()->all($owner, $repo) as $hook) {
            if ($hook['config']['url'] === $url) {
                $this->client->repositories()->hooks()->remove($owner, $repo, $hook['id']);
            }
        }
    }

    /**
     * @return mixed[]
     */
    private function privateRepos(): array
    {
        $paginator = new ResultPager($this->client);

        return $paginator->fetchAll($this->client->currentUser(), 'repositories');
    }

    /**
     * @return mixed[]
     */
    private function organizationRepos(string $organization): array
    {
        $paginator = new ResultPager($this->client);

        return $paginator->fetchAll($this->client->organization(), 'repositories', [$organization]);
    }

    /**
     * @return mixed[]
     */
    private function memberships(): array
    {
        return $this->client->currentUser()->memberships()->all();
    }
}
