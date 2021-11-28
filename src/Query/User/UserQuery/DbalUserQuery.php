<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\UserQuery;

use Buddy\Repman\Query\Filter;
use Buddy\Repman\Query\User\Model\ApiToken;
use Buddy\Repman\Query\User\Model\OAuthToken;
use Buddy\Repman\Query\User\UserQuery;
use Doctrine\DBAL\Connection;

final class DbalUserQuery implements UserQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return OAuthToken[]
     */
    public function findAllOAuthTokens(string $userId): array
    {
        return array_map(function (array $data): OAuthToken {
            return new OAuthToken(
                $data['type'],
                new \DateTimeImmutable($data['created_at'])
            );
        }, $this->connection->fetchAllAssociative('
            SELECT type, created_at
            FROM user_oauth_token
            WHERE user_id = :user_id
            ORDER BY created_at DESC', [
            'user_id' => $userId,
        ]));
    }

    public function hasOAuthAccessToken(string $userId, string $type): bool
    {
        return $this->connection->fetchAssociative('SELECT access_token FROM user_oauth_token WHERE user_id = :user_id AND type = :type', [
            'user_id' => $userId,
            'type' => $type,
        ]) !== false;
    }

    /**
     * @return ApiToken[]
     */
    public function getAllApiTokens(string $userId, Filter $filter): array
    {
        return array_map(function (array $data): ApiToken {
            return $this->hydrateToken($data);
        }, $this->connection->fetchAllAssociative('
            SELECT name, value, created_at, last_used_at
            FROM user_api_token
            WHERE user_id = :id
            ORDER BY UPPER(name) ASC
            LIMIT :limit OFFSET :offset', [
            'id' => $userId,
            'limit' => $filter->getLimit(),
            'offset' => $filter->getOffset(),
        ]));
    }

    public function apiTokenCount(string $userId): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(value) FROM user_api_token WHERE user_id = :id',
                ['id' => $userId]
            );
    }

    /**
     * @param array<string,mixed> $data
     */
    private function hydrateToken(array $data): ApiToken
    {
        return new ApiToken(
            $data['name'],
            $data['value'],
            new \DateTimeImmutable($data['created_at']),
            $data['last_used_at'] !== null ? new \DateTimeImmutable($data['last_used_at']) : null
        );
    }
}
