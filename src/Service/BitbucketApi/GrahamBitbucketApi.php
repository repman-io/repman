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
}
