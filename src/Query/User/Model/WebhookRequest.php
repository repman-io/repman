<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class WebhookRequest
{
    public function __construct(private readonly string $date, private readonly ?string $ip, private readonly ?string $userAgent)
    {
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
