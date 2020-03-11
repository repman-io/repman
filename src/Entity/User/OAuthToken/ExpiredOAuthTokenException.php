<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\User\OAuthToken;

final class ExpiredOAuthTokenException extends \Exception
{
    private string $type;

    public function __construct(string $type)
    {
        parent::__construct(sprintf('%s OAuth access token has expired', $type));
        $this->type = $type;
    }

    public function type(): string
    {
        return $this->type;
    }
}
