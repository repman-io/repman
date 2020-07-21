<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TelemetryEndpoint implements Endpoint
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function send(string $userAgent, Entry $entry): void
    {
        $response = $this->client->request(
            'POST',
            'https://telemetry.repman.io',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => $userAgent,
                ],
                'body' => $entry->toString(),
            ]
        );

        if ($response->getStatusCode() >= 300) {
            throw new \RuntimeException(sprintf('Error while sending telemetry data. HTTP error: %d', $response->getStatusCode()));
        }
    }
}
