<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Service\Organization\OrganizationParamConverter;
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
        $converter->apply(new Request(), new ParamConverter([]));
    }
}
