<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User;

use Buddy\Repman\Query\User\Model\OAuthToken;

interface UserQuery
{
    /**
     * @return OAuthToken[]
     */
    public function findAllOAuthTokens(string $userId): array;
}
