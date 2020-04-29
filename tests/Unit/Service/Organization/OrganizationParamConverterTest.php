<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Service\Organization\OrganizationParamConverter;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class OrganizationParamConverterTest extends TestCase
{
    public function testThrowExceptionWhenOrganizationParamIsMissing(): void
    {
        $converter = new OrganizationParamConverter($this->getMockBuilder(OrganizationQuery::class)->getMock());

        $this->expectException(BadRequestHttpException::class);
        $converter->apply(new Request(), new ParamConverter(['name' => 'organization']));
    }

    public function testCheckIfOrganizationIsAlreadyConverted(): void
    {
        $queryMock = $this->getMockBuilder(OrganizationQuery::class)->getMock();
        $queryMock->expects(self::never())->method('getByAlias');

        $converter = new OrganizationParamConverter($queryMock);

        $converter->apply(new Request([], [], ['organization' => new Organization(
            '10b86f64-ccf5-4ef8-a99f-b7cafe1fcf37',
            'Buddy',
            'buddy',
            [new Organization\Member('9a1c9f23-23bf-4dc0-8d10-03848867d7f4', 'email', 'owner')]
        )]), new ParamConverter(['name' => 'organization']));
    }

    public function testConvertOrganization(): void
    {
        $organization = new Organization(
            '10b86f64-ccf5-4ef8-a99f-b7cafe1fcf37',
            'Buddy',
            'buddy',
            [new Organization\Member('9a1c9f23-23bf-4dc0-8d10-03848867d7f4', 'email', 'owner')]
        );
        $queryMock = $this->getMockBuilder(OrganizationQuery::class)->getMock();
        $queryMock->expects(self::once())->method('getByAlias')->with('buddy')->willReturn(Option::some($organization));

        $converter = new OrganizationParamConverter($queryMock);

        $converter->apply($request = new Request([], [], ['organization' => 'buddy']), new ParamConverter(['name' => 'organization']));

        self::assertEquals($organization, $request->attributes->get('organization'));
    }
}
