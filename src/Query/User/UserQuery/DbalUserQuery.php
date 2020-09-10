<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\UserQuery;

use Buddy\Repman\Entity\User\OAuthToken\ExpiredOAuthTokenException;
use Buddy\Repman\Query\User\Model\ApiToken;
use Buddy\Repman\Query\User\Model\OAuthToken;
use Buddy\Repman\Query\User\UserQuery;
use Doctrine\DBAL\Connection;
use Munus\Control\Option;

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
        }, $this->connection->fetchAll('
            SELECT type, created_at
            FROM user_oauth_token
            WHERE user_id = :user_id
            ORDER BY created_at DESC', [
            ':user_id' => $userId,
        ]));
    }

    /**
     * @return Option<string>
     */
    public function findOAuthAccessToken(string $userId, string $type): Option
    {
        $data = $this->connection->fetchAssoc('SELECT access_token, expires_at FROM user_oauth_token WHERE user_id = :user_id AND type = :type', [
            ':user_id' => $userId,
            ':type' => $type,
        ]);

        if ($data === false) {
            return Option::none();
        }

        if (
            $data['expires_at'] !== null &&
            ($expiresAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['expires_at'])) !== false &&
            (new \DateTimeImmutable()) > $expiresAt->modify('-1 min')) {
            throw new ExpiredOAuthTokenException($userId, $type);
        }

        return Option::some($data['access_token']);
    }

    /**
     * @return ApiToken[]
     */
    public function findAllApiTokens(string $userId, int $limit = 20, int $offset = 0): array
    {
        return array_map(function (array $data): ApiToken {
            return $this->hydrateToken($data);
        }, $this->connection->fetchAll('
            SELECT name, value, created_at, last_used_at
            FROM user_api_token
            WHERE user_id = :id
            ORDER BY UPPER(name) ASC
            LIMIT :limit OFFSET :offset', [
            ':id' => $userId,
            ':limit' => $limit,
            ':offset' => $offset,
        ]));
    }

    public function apiTokenCount(string $userId): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(value) FROM user_api_token WHERE user_id = :id',
                [':id' => $userId]
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
