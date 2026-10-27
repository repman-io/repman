<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\BuddyApi;

use Buddy\Repman\Service\Integration\BuddyApi;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RestBuddyApi implements BuddyApi
{
    private const EMAILS_URL = 'https://api.buddy.works/user/emails';

    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function primaryEmail(string $accessToken): string
    {
        try {
            $body = $this->client->request('GET', self::EMAILS_URL, [
                'auth_bearer' => $accessToken,
                'headers' => ['Accept' => 'application/json'],
                'timeout' => 10,
            ])->toArray();
        } catch (ExceptionInterface $exception) {
            throw new BuddyApiException($exception->getMessage(), $exception->getCode(), $exception);
        }

        foreach ($body['emails'] ?? [] as $email) {
            if (($email['confirmed'] ?? false) === true && isset($email['email'])) {
                return (string) $email['email'];
            }
        }

        throw new BuddyApiException('Missing confirmed e-mail');
    }
}
