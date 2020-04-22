<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Validator;

use Buddy\Repman\Query\Admin\Model\User;
use Buddy\Repman\Query\Admin\UserQuery;
use Buddy\Repman\Validator\UniqueEmail;
use Buddy\Repman\Validator\UniqueEmailValidator;
use Munus\Control\Option;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class UniqueEmailValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var MockObject|UserQuery
     */
    private $userQueryMock;

    protected function createValidator(): UniqueEmailValidator
    {
        $this->userQueryMock = $this->getMockBuilder(UserQuery::class)->getMock();

        return new UniqueEmailValidator($this->userQueryMock);
    }

    public function testNoViolation(): void
    {
        $this->userQueryMock
            ->expects(self::once())
            ->method('getByEmail')
            ->willReturn(Option::none());

        $this->validator->validate('test@buddy.works', new UniqueEmail());
        $this->assertNoViolation();

        $this->validator->validate(null, new UniqueEmail());
        $this->assertNoViolation();
    }

    public function testEmailExistViolation(): void
    {
        $this->userQueryMock
            ->expects(self::once())
            ->method('getByEmail')
            ->willReturn(Option::some(new User('55fe42eb-d527-4a64-bd48-fb3f54372673', 'test@buddy.works', 'enabled', [])));

        $this->validator->validate('test@buddy.works', new UniqueEmail());

        $this->buildViolation('Email "{{ value }}" already exists.')
            ->setParameter('{{ value }}', 'test@buddy.works')
            ->assertRaised();
    }

    public function testEmailLowerCasedViolation(): void
    {
        $this->userQueryMock
            ->expects(self::once())
            ->method('getByEmail')
            ->with('test@buddy.works')
            ->willReturn(Option::some(new User('55fe42eb-d527-4a64-bd48-fb3f54372673', 'test@buddy.works', 'enabled', [])));

        $this->validator->validate('test@BUDDY.works', new UniqueEmail());

        $this->buildViolation('Email "{{ value }}" already exists.')
            ->setParameter('{{ value }}', 'test@buddy.works')
            ->assertRaised();
    }
}
