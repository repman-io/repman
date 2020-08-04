<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class TelemetryEndpoint implements Endpoint
{
    const URL = 'https://telemetry.repman.io';

    const HEADERS = ['Content-Type' => 'application/json'];

    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function send(Entry $entry): void
    {
        $this->checkResponse($this->client->request('POST', $this->telemetryUrl(), [
                'headers' => self::HEADERS,
                'body' => json_encode($entry),
            ]
        ));
    }

    public function addTechnicalEmail(TechnicalEmail $email): void
    {
        $this->checkResponse($this->client->request('POST', $this->emailUrl(), [
                'headers' => self::HEADERS,
                'body' => json_encode($email),
            ]
        ));
    }

    public function removeTechnicalEmail(TechnicalEmail $email): void
    {
        $this->checkResponse($this->client->request('DELETE', $this->emailUrl(), [
                'headers' => self::HEADERS,
                'body' => json_encode($email),
            ]
        ));
    }

    private function telemetryUrl(): string
    {
        return self::URL;
    }

    private function emailUrl(): string
    {
        return self::URL.'/email';
    }

    private function checkResponse(ResponseInterface $response): void
    {
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException(sprintf('Error while sending telemetry data. HTTP error: %d', $response->getStatusCode()));
        }
    }
}
