<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\OrganizationQuery;

use Buddy\Repman\Query\Filter;
use Buddy\Repman\Query\User\Model\Installs;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Invitation;
use Buddy\Repman\Query\User\Model\Organization\Member;
use Buddy\Repman\Query\User\Model\Organization\Token;
use Buddy\Repman\Query\User\OrganizationQuery;
use Doctrine\DBAL\Connection;
use Munus\Control\Option;

final class DbalOrganizationQuery implements OrganizationQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Option<Organization>
     */
    public function getByAlias(string $alias): Option
    {
        $data = $this->connection->fetchAssociative(
            'SELECT id, name, alias, has_anonymous_access
            FROM "organization" WHERE alias = :alias', [
            'alias' => $alias,
        ]);

        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydrateOrganization($data));
    }

    public function getByInvitation(string $token, string $email): Option
    {
        $data = $this->connection->fetchAssociative(
            'SELECT o.id, o.name, o.alias, o.has_anonymous_access
            FROM "organization" o
            JOIN organization_invitation i ON o.id = i.organization_id
            WHERE i.token = :token AND i.email = :email
        ', [
            'token' => $token,
            'email' => $email,
        ]);

        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydrateOrganization($data));
    }

    /**
     * @return Token[]
     */
    public function findAllTokens(string $organizationId, Filter $filter): array
    {
        return array_map(function (array $data): Token {
            return $this->hydrateToken($data);
        }, $this->connection->fetchAllAssociative('
            SELECT name, value, created_at, last_used_at
            FROM organization_token
            WHERE organization_id = :id
            ORDER BY UPPER(name) ASC
            LIMIT :limit OFFSET :offset', [
            'id' => $organizationId,
            'limit' => $filter->getLimit(),
            'offset' => $filter->getOffset(),
        ]));
    }

    public function findAnyToken(string $organizationId): ?string
    {
        $token = $this->connection->fetchOne('SELECT value FROM organization_token WHERE organization_id = :id', [
            'id' => $organizationId,
        ]);

        return $token !== false ? $token : null;
    }

    public function tokenCount(string $organizationId): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(value) FROM organization_token WHERE organization_id = :id',
                ['id' => $organizationId]
            );
    }

    public function getInstalls(string $organizationId, int $lastDays = 30): Installs
    {
        $packagesId = array_column($this->connection->fetchAllAssociative('SELECT id FROM organization_package WHERE organization_id = :id', ['id' => $organizationId]), 'id');

        return new Installs(
            array_map(function (array $row): Installs\Day {
                return new Installs\Day($row['date'], $row['count']);
            }, $this->connection->fetchAllAssociative('SELECT * FROM (SELECT COUNT(package_id), date FROM organization_package_download WHERE date > :date AND package_id IN (:packages) GROUP BY date) AS installs ORDER BY date ASC', [
                'date' => (new \DateTimeImmutable())->modify(sprintf('-%s days', $lastDays))->format('Y-m-d'),
                'packages' => $packagesId,
            ], ['packages' => Connection::PARAM_STR_ARRAY])),
            $lastDays,
            (int) $this->connection->fetchOne(
                'SELECT COUNT(package_id) FROM organization_package_download WHERE package_id IN (:packages)',
                ['packages' => $packagesId],
                ['packages' => Connection::PARAM_STR_ARRAY]
            )
        );
    }

    public function findAllInvitations(string $organizationId, Filter $filter): array
    {
        return array_map(function (array $row): Invitation {
            return new Invitation(
                $row['email'],
                $row['role'],
                $row['token']
            );
        }, $this->connection->fetchAllAssociative('
            SELECT email, role, token
            FROM organization_invitation
            WHERE organization_id = :id
            ORDER BY email ASC
            LIMIT :limit OFFSET :offset', [
            'id' => $organizationId,
            'limit' => $filter->getLimit(),
            'offset' => $filter->getOffset(),
        ]));
    }

    public function invitationsCount(string $organizationId): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(*) FROM organization_invitation WHERE organization_id = :id',
                ['id' => $organizationId]
            );
    }

    /**
     * @return Member[]
     */
    public function findAllMembers(string $organizationId, Filter $filter): array
    {
        return array_map(function (array $row): Member {
            return new Member(
                $row['id'],
                $row['email'],
                $row['role']
            );
        }, $this->connection->fetchAllAssociative('
            SELECT u.id, u.email, m.role
            FROM organization_member AS m
            JOIN "user" u ON u.id = m.user_id
            WHERE m.organization_id = :id
            ORDER BY u.email ASC
            LIMIT :limit OFFSET :offset', [
            'id' => $organizationId,
            'limit' => $filter->getLimit(),
            'offset' => $filter->getOffset(),
        ]));
    }

    public function membersCount(string $organizationId): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(*) FROM organization_member WHERE organization_id = :id',
                ['id' => $organizationId]
            );
    }

    public function isMember(string $organizationId, string $email): bool
    {
        return false !== $this
            ->connection
            ->fetchOne(
                'SELECT 1 FROM organization_member AS m JOIN "user" u ON u.id = m.user_id WHERE organization_id = :id AND u.email = :email',
                [
                    'id' => $organizationId,
                    'email' => $email,
                ]
            );
    }

    public function isInvited(string $organizationId, string $email): bool
    {
        return false !== $this
            ->connection
            ->fetchOne(
                'SELECT 1 FROM organization_invitation WHERE organization_id = :id AND email = :email',
                [
                    'id' => $organizationId,
                    'email' => $email,
                ]
            );
    }

    /**
     * @return Option<Token>
     */
    public function findToken(string $organizationId, string $value): Option
    {
        $data = $this->connection->fetchAssociative(
            'SELECT name, value, created_at, last_used_at
            FROM organization_token
            WHERE organization_id = :organization_id AND value = :value
            LIMIT 1', [
            'organization_id' => $organizationId,
            'value' => $value,
        ]);

        return $data === false ? Option::none() : Option::some($this->hydrateToken($data));
    }

    /**
     * @param array<mixed> $data
     */
    private function hydrateOrganization(array $data): Organization
    {
        $members = $this->connection->fetchAllAssociative('SELECT m.user_id, m.role, u.email FROM organization_member m JOIN "user" u ON u.id = m.user_id WHERE m.organization_id = :id', [
            'id' => $data['id'],
        ]);

        return new Organization(
            $data['id'],
            $data['name'],
            $data['alias'],
            array_map(fn (array $row) => new Member($row['user_id'], $row['email'], $row['role']), $members),
            $data['has_anonymous_access'],
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    private function hydrateToken(array $data): Token
    {
        return new Token(
            $data['name'],
            $data['value'],
            new \DateTimeImmutable($data['created_at']),
            $data['last_used_at'] !== null ? new \DateTimeImmutable($data['last_used_at']) : null
        );
    }
}
