<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User;

use Buddy\Repman\Query\User\Model\Installs;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Invitation;
use Buddy\Repman\Query\User\Model\Organization\Member;
use Buddy\Repman\Query\User\Model\Organization\Token;
use Munus\Control\Option;

interface OrganizationQuery
{
    /**
     * @return Option<Organization>
     */
    public function getByAlias(string $alias): Option;

    /**
     * @return Option<Organization>
     */
    public function getByInvitation(string $token, string $email): Option;

    /**
     * @return Token[]
     */
    public function findAllTokens(string $organizationId, int $limit = 20, int $offset = 0): array;

    public function tokenCount(string $organizationId): int;

    public function getInstalls(string $organizationId, int $lastDays = 30): Installs;

    /**
     * @return Member[]
     */
    public function findAllMembers(string $organizationId, int $limit = 20, int $offset = 0): array;

    public function membersCount(string $organizationId): int;

    public function isMember(string $organizationId, string $email): bool;

    /**
     * @return Invitation[]
     */
    public function findAllInvitations(string $organizationId, int $limit = 20, int $offset = 0): array;

    public function invitationsCount(string $organizationId): int;

    public function isInvited(string $organizationId, string $email): bool;

    /**
     * @return Option<Token>
     */
    public function findToken(string $organizationId, string $value): Option;

    /**
     * @return Option<Token>
     */
    public function findTokenByName(string $organizationId, string $name): Option;
}
