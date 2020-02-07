<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Service\Organization\OrganizationVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrganizationVoterTest extends TestCase
{
    public function testSupportOnlyUserInstance(): void
    {
        $voter = new OrganizationVoter($this->getMockBuilder(OrganizationQuery::class)->getMock());

        self::assertEquals(Voter::ACCESS_DENIED, $voter->vote(
            new AnonymousToken('secret', 'anon', []),
            'any',
            ['ROLE_ORGANIZATION_MEMBER']
        ));
    }
}
