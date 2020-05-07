<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Service\Organization\OrganizationParamConverter;
use Buddy\Repman\Tests\MotherObject\Query\OrganizationMother;
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

        $converter->apply(new Request([], [], [
            'organization' => OrganizationMother::some(),
        ]), new ParamConverter(['name' => 'organization']));
    }

    public function testConvertOrganization(): void
    {
        $organization = OrganizationMother::some();
        $queryMock = $this->getMockBuilder(OrganizationQuery::class)->getMock();
        $queryMock->expects(self::once())->method('getByAlias')->with('buddy')->willReturn(Option::some($organization));

        $converter = new OrganizationParamConverter($queryMock);

        $converter->apply($request = new Request([], [], ['organization' => 'buddy']), new ParamConverter(['name' => 'organization']));

        self::assertEquals($organization, $request->attributes->get('organization'));
    }
}
