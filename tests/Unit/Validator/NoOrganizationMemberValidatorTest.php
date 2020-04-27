<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Validator;

use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Validator\NotOrganizationMember;
use Buddy\Repman\Validator\NotOrganizationMemberValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class NoOrganizationMemberValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var MockObject|OrganizationQuery
     */
    private $organizationQuery;

    protected function createValidator(): NotOrganizationMemberValidator
    {
        $this->organizationQuery = $this->getMockBuilder(OrganizationQuery::class)->getMock();

        return new NotOrganizationMemberValidator($this->organizationQuery);
    }

    public function testNoViolation(): void
    {
        $this->organizationQuery
            ->expects(self::once())
            ->method('isInvited')
            ->willReturn(false);

        $this->organizationQuery
            ->expects(self::once())
            ->method('isMember')
            ->willReturn(false);

        $this->validator->validate('test@buddy.works', $constraint = new NotOrganizationMember(['organizationId' => 'e71e02b2-ef2c-4f17-bfba-c1d8ecf958e0']));
        $this->assertNoViolation();

        $this->validator->validate('', $constraint);
        $this->assertNoViolation();
    }

    public function testInvitationExistViolation(): void
    {
        $this->organizationQuery
            ->expects(self::once())
            ->method('isInvited')
            ->willReturn(true);

        $this->organizationQuery
            ->expects(self::once())
            ->method('isMember')
            ->willReturn(false);

        $this->validator->validate('test@buddy.works', $constraint = new NotOrganizationMember(['organizationId' => 'e71e02b2-ef2c-4f17-bfba-c1d8ecf958e0']));

        $this->buildViolation($constraint->alreadyInvitedMessage)
            ->setParameter('{{ value }}', 'test@buddy.works')
            ->assertRaised();
    }

    public function testMemberExistViolation(): void
    {
        $this->organizationQuery
            ->expects(self::once())
            ->method('isInvited')
            ->willReturn(false);

        $this->organizationQuery
            ->expects(self::once())
            ->method('isMember')
            ->willReturn(true);

        $this->validator->validate('test@buddy.works', $constraint = new NotOrganizationMember(['organizationId' => 'e71e02b2-ef2c-4f17-bfba-c1d8ecf958e0']));

        $this->buildViolation($constraint->alreadyMemberMessage)
            ->setParameter('{{ value }}', 'test@buddy.works')
            ->assertRaised();
    }
}
