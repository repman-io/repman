<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User;

use Buddy\Repman\Query\Filter;
use Buddy\Repman\Query\User\Model\ApiToken;
use Buddy\Repman\Query\User\Model\OAuthToken;

interface UserQuery
{
    /**
     * @return OAuthToken[]
     */
    public function findAllOAuthTokens(string $userId): array;

    public function hasOAuthAccessToken(string $userId, string $type): bool;

    /**
     * @return ApiToken[]
     */
    public function getAllApiTokens(string $userId, Filter $filter): array;

    public function apiTokenCount(string $userId): int;
}
