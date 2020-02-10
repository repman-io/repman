<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Service\Organization\PackageParamConverter;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class PackageParamConverterTest extends TestCase
{
    public function testThrowExceptionWhenPackageParamIsMissing(): void
    {
        $converter = new PackageParamConverter($this->getMockBuilder(PackageQuery::class)->getMock());

        $this->expectException(BadRequestHttpException::class);
        $converter->apply(new Request(), new ParamConverter([]));
    }
}
