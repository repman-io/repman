<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api;

use Buddy\Repman\Query\Api\Model\Organization;
use Buddy\Repman\Query\Api\Model\Token;
use Munus\Control\Option;

interface OrganizationQuery
{
    /**
     * @return Option<Organization>
     */
    public function getById(string $id): Option;

    /**
     * @return Organization[]
     */
    public function getUserOrganizations(string $userId, int $limit = 20, int $offset = 0): array;

    public function userOrganizationsCount(string $userId): int;

    /**
     * @return Token[]
     */
    public function findAllTokens(string $organizationId, int $limit = 20, int $offset = 0): array;

    public function tokenCount(string $organizationId): int;

    /**
     * @return Option<Token>
     */
    public function findToken(string $organizationId, string $value): Option;

    /**
     * @return Option<Token>
     */
    public function findTokenByName(string $organizationId, string $name): Option;
}
