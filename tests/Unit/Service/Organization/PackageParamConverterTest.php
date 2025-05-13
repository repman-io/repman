<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Service\Organization\PackageParamConverter;
use Buddy\Repman\Tests\MotherObject\Query\OrganizationMother;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PackageParamConverterTest extends TestCase
{
    public function testThrowExceptionWhenPackageParamIsMissing(): void
    {
        $converter = new PackageParamConverter($this->getMockBuilder(PackageQuery::class)->getMock());

        $this->expectException(BadRequestHttpException::class);
        $converter->apply(new Request(), new ParamConverter([]));
    }

    public function testConvertPackage(): void
    {
        $package = new Package($id = '12cfc5f0-19d7-4144-916c-cfbbf9384c29', 'e20ea9cc-de3e-4d10-9e81-e30b6c3d217c', 'vcs', 'https://some.url');
        $queryMock = $this->createMock(PackageQuery::class);
        $queryMock->expects(self::once())->method('getById')->willReturn(Option::some($package));

        $converter = new PackageParamConverter($queryMock);
        $converter->apply($request = new Request([], [], ['package' => $id]), new ParamConverter(['name' => 'package']));

        $this->assertEquals($package, $request->attributes->get('package'));
    }

    public function testCheckIfPackageBelongsToOrganization(): void
    {
        $package = new Package($id = '12cfc5f0-19d7-4144-916c-cfbbf9384c29', 'e20ea9cc-de3e-4d10-9e81-e30b6c3d217c', 'vcs', 'https://some.url');
        $queryMock = $this->createMock(PackageQuery::class);
        $queryMock->expects(self::once())->method('getById')->willReturn(Option::some($package));

        $converter = new PackageParamConverter($queryMock);

        $this->expectException(NotFoundHttpException::class);
        $converter->apply($request = new Request([], [], [
            'package' => $id,
            'organization' => OrganizationMother::some(),
        ]), new ParamConverter(['name' => 'package']));
    }
}
