<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class WebhookRequest
{
    private string $date;
    private ?string $ip;
    private ?string $userAgent;

    public function __construct(string $date, ?string $ip, ?string $userAgent)
    {
        $this->date = $date;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
    }

    public function date(): string
    {
        return $this->date;
    }

    public function ip(): ?string
    {
        return $this->ip;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }
}
