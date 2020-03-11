<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\User\OAuthToken;

final class ExpiredOAuthTokenException extends \Exception
{
    private string $userId;
    private string $type;

    public function __construct(string $userId, string $type)
    {
        parent::__construct(sprintf('%s OAuth access token has expired', $type));
        $this->userId = $userId;
        $this->type = $type;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function userId(): string
    {
        return $this->userId;
    }
}
