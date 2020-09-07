<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User;

use Buddy\Repman\Query\User\Model\ApiToken;
use Buddy\Repman\Query\User\Model\OAuthToken;
use Munus\Control\Option;

interface UserQuery
{
    /**
     * @return OAuthToken[]
     */
    public function findAllOAuthTokens(string $userId): array;

    /**
     * @return Option<string>
     */
    public function findOAuthAccessToken(string $userId, string $type): Option;

    /**
     * @return ApiToken[]
     */
    public function getAllApiTokens(string $userId, int $limit = 20, int $offset = 0): array;

    public function apiTokenCount(string $userId): int;
}
