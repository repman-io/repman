<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use Buddy\Repman\Query\User\Model\Organization\Member;
use Munus\Control\Option;

final class Organization
{
    /**
     * @var Member[]
     */
    private readonly array $members;

    /**
     * @param Member[] $members
     */
    public function __construct(private readonly string $id, private readonly string $name, private readonly string $alias, array $members, private readonly bool $hasAnonymousAccess)
    {
        $this->members = array_map(fn (Member $member) => $member, $members);
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
            if ($member->isOwner() && $member->userId() === $userId) {
                return true;
            }
        }

        return false;
    }

    public function isLastOwner(string $userId): bool
    {
        $owners = array_values(array_filter($this->members, fn (Member $member) => $member->isOwner()));

        return count($owners) === 1 && $owners[0]->userId() === $userId;
    }

    /**
     * @return Option<Member>
     */
    public function getMember(string $userId): Option
    {
        foreach ($this->members as $member) {
            if ($member->userId() === $userId) {
                return Option::some($member);
            }
        }

        return Option::none();
    }

    public function hasAnonymousAccess(): bool
    {
        return $this->hasAnonymousAccess;
    }
}
