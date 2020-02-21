<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\GitHubApi;

use Buddy\Repman\Service\GitHubApi;
use Github\Client;

final class KnpGitHubApi implements GitHubApi
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function primaryEmail(string $accessToken): string
    {
        $this->client->authenticate($accessToken, null, Client::AUTH_JWT);
        foreach ($this->client->currentUser()->emails()->all() as $email) {
            if ($email['primary'] === true) {
                return $email['email'];
            }
        }

        throw new \RuntimeException('Primary e-mail not found.');
    }
}
