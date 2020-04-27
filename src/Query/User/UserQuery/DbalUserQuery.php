<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\UserQuery;

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
        }, $this->connection->fetchAll('
            SELECT type, created_at
            FROM user_oauth_token
            WHERE user_id = :user_id
            ORDER BY created_at DESC', [
            ':user_id' => $userId,
        ]));
    }
}
