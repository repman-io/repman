<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Entity\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class MemberTest extends TestCase
{
    public function testTestRoleValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Member(
            Uuid::uuid4(),
            $user = new User(Uuid::uuid4(), 'test@buddy.works', 'token', []),
            new Organization(Uuid::uuid4(), $user, 'repman', 'repman'),
            'bad-role'
        );
    }

    public function testRoleValidationOnRoleChange(): void
    {
        $member = new Member(
            Uuid::uuid4(),
            $user = new User(Uuid::uuid4(), 'test@buddy.works', 'token', []),
            new Organization(Uuid::uuid4(), $user, 'repman', 'repman'),
            Member::ROLE_OWNER
        );

        $this->expectException(\InvalidArgumentException::class);

        $member->changeRole('invalid');
    }
}
