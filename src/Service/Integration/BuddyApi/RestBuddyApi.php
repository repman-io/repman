<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\BuddyApi;

use Buddy\Buddy;
use Buddy\Repman\Service\Integration\BuddyApi;

final class RestBuddyApi implements BuddyApi
{
    public function __construct(private readonly Buddy $client)
    {
    }

    public function primaryEmail(string $accessToken): string
    {
        $body = $this->client->getApiEmails()->getAuthenticatedUserEmails($accessToken)->getBody();
        foreach ($body['emails'] as $email) {
            if ($email['confirmed'] === true) {
                return $email['email'];
            }
        }

        throw new BuddyApiException('Missing confirmed e-mail');
    }
}
