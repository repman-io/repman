<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\MotherObject\Query;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Member;

final class OrganizationMother
{
    public static function some(): Organization
    {
        return new Organization(
            '448e8c58-dd52-4db6-92bd-ab102d5273de',
            'Repman',
            'repman',
            [new Organization\Member('5c1b8e35-fe7b-4418-b722-ec9cbbf2598a', 'test@repman.io', 'owner')],
            false
        );
    }

    public static function withMember(Member $member): Organization
    {
        return new Organization(
            '448e8c58-dd52-4db6-92bd-ab102d5273de',
            'Repman',
            'repman',
            [$member],
            false
        );
    }
}
