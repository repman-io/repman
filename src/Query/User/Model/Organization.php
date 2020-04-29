<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use Buddy\Repman\Query\User\Model\Organization\Member;

final class Organization
{
    private string $id;
    private string $name;
    private string $alias;
    /**
     * @var Member[]
     */
    private array $members;

    private ?string $token;

    /**
     * @param Member[] $members
     */
    public function __construct(string $id, string $name, string $alias, array $members, ?string $token = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
        $this->members = array_map(fn (Member $member) => $member, $members);
        $this->token = $token;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    public function token(): ?string
    {
        return $this->token;
    }

    public function isMember(string $userId): bool
    {
        foreach ($this->members as $member) {
            if ($member->userId() === $userId) {
                return true;
            }
        }

        return false;
    }

    public function isOwner(string $userId): bool
    {
        foreach ($this->members as $member) {
            if ($member->role() === 'owner' && $member->userId() === $userId) {
                return true;
            }
        }

        return false;
    }
}
