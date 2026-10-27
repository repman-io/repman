<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Member;
use Buddy\Repman\Query\User\Model\Organization\Token;
use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\Model\PackageDetails;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Service\Organization\MemberValueResolver;
use Buddy\Repman\Service\Organization\OrganizationValueResolver;
use Buddy\Repman\Service\Organization\PackageDetailsValueResolver;
use Buddy\Repman\Service\Organization\PackageValueResolver;
use Buddy\Repman\Service\Organization\TokenValueResolver;
use Buddy\Repman\Tests\MotherObject\Query\OrganizationMother;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ValueResolversTest extends TestCase
{
    private const PACKAGE_ID = '12cfc5f0-19d7-4144-916c-cfbbf9384c29';

    public function testOrganizationResolverIgnoresOtherTypes(): void
    {
        $resolver = new OrganizationValueResolver(self::createStub(OrganizationQuery::class));

        self::assertSame([], [...$resolver->resolve(new Request(), self::argument('organization', 'string'))]);
    }

    public function testThrowExceptionWhenOrganizationParamIsMissing(): void
    {
        $resolver = new OrganizationValueResolver(self::createStub(OrganizationQuery::class));

        $this->expectException(BadRequestHttpException::class);
        [...$resolver->resolve(new Request(), self::argument('organization', Organization::class))];
    }

    public function testThrowNotFoundWhenOrganizationDoesNotExist(): void
    {
        $query = $this->createMock(OrganizationQuery::class);
        $query->method('getByAlias')->with('missing')->willReturn(Option::none());

        $this->expectException(NotFoundHttpException::class);
        [...(new OrganizationValueResolver($query))->resolve(new Request([], [], ['organization' => 'missing']), self::argument('organization', Organization::class))];
    }

    public function testResolveOrganizationOnceAndKeepItInRequest(): void
    {
        $organization = OrganizationMother::some();
        $query = $this->createMock(OrganizationQuery::class);
        $query->expects($this->once())->method('getByAlias')->with('repman')->willReturn(Option::some($organization));
        $resolver = new OrganizationValueResolver($query);
        $request = new Request([], [], ['organization' => 'repman']);

        self::assertSame([$organization], [...$resolver->resolve($request, self::argument('organization', Organization::class))]);
        self::assertSame($organization, $request->attributes->get('organization'));
        self::assertSame([$organization], [...$resolver->resolve($request, self::argument('organization', Organization::class))]);
    }

    public function testThrowExceptionWhenPackageParamIsMissing(): void
    {
        $resolver = new PackageValueResolver(self::createStub(PackageQuery::class), $this->organizationResolver());

        $this->expectException(BadRequestHttpException::class);
        [...$resolver->resolve(new Request(), self::argument('package', Package::class))];
    }

    public function testResolvePackage(): void
    {
        $package = $this->package(OrganizationMother::some()->id());
        $query = $this->createMock(PackageQuery::class);
        $query->expects($this->once())->method('getById')->with(self::PACKAGE_ID)->willReturn(Option::some($package));

        $resolver = new PackageValueResolver($query, $this->organizationResolver());
        $request = new Request([], [], ['package' => self::PACKAGE_ID]);

        self::assertSame([$package], [...$resolver->resolve($request, self::argument('package', Package::class))]);
        self::assertSame($package, $request->attributes->get('package'));
    }

    public function testPackageMustBelongToOrganization(): void
    {
        $query = self::createStub(PackageQuery::class);
        $query->method('getById')->willReturn(Option::some($this->package('e20ea9cc-de3e-4d10-9e81-e30b6c3d217c')));
        $resolver = new PackageValueResolver($query, $this->organizationResolver());

        $this->expectException(NotFoundHttpException::class);
        [...$resolver->resolve(
            new Request([], [], ['package' => self::PACKAGE_ID, 'organization' => OrganizationMother::some()]),
            self::argument('package', Package::class)
        )];
    }

    /**
     * The package argument may be resolved before the organization argument; the ownership check
     * must not depend on that order.
     */
    public function testPackageOwnershipIsCheckedWhenOrganizationIsNotResolvedYet(): void
    {
        $query = self::createStub(PackageQuery::class);
        $query->method('getById')->willReturn(Option::some($this->package('e20ea9cc-de3e-4d10-9e81-e30b6c3d217c')));
        $resolver = new PackageValueResolver($query, $this->organizationResolver());

        $this->expectException(NotFoundHttpException::class);
        [...$resolver->resolve(
            new Request([], [], ['package' => self::PACKAGE_ID, 'organization' => 'repman']),
            self::argument('package', Package::class)
        )];
    }

    public function testThrowExceptionWhenPackageDetailsParamIsMissing(): void
    {
        $resolver = new PackageDetailsValueResolver(self::createStub(PackageQuery::class), $this->organizationResolver());

        $this->expectException(BadRequestHttpException::class);
        [...$resolver->resolve(new Request(), self::argument('package', PackageDetails::class))];
    }

    public function testResolvePackageDetails(): void
    {
        $package = new PackageDetails(self::PACKAGE_ID, OrganizationMother::some()->id(), 'https://app.repman.io/');
        $query = $this->createMock(PackageQuery::class);
        $query->expects($this->once())->method('getDetailsById')->willReturn(Option::some($package));

        $resolver = new PackageDetailsValueResolver($query, $this->organizationResolver());

        self::assertSame([$package], [...$resolver->resolve(
            new Request([], [], ['package' => self::PACKAGE_ID, 'organization' => 'repman']),
            self::argument('package', PackageDetails::class)
        )]);
    }

    public function testPackageDetailsMustBelongToOrganization(): void
    {
        $query = self::createStub(PackageQuery::class);
        $query->method('getDetailsById')->willReturn(Option::some(new PackageDetails(self::PACKAGE_ID, 'e20ea9cc-de3e-4d10-9e81-e30b6c3d217c', 'https://app.repman.io/')));
        $resolver = new PackageDetailsValueResolver($query, $this->organizationResolver());

        $this->expectException(NotFoundHttpException::class);
        [...$resolver->resolve(
            new Request([], [], ['package' => self::PACKAGE_ID, 'organization' => OrganizationMother::some()]),
            self::argument('package', PackageDetails::class)
        )];
    }

    public function testThrowExceptionWhenMemberParamIsMissing(): void
    {
        $resolver = new MemberValueResolver($this->organizationResolver());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing member parameter in request');
        [...$resolver->resolve(new Request(), self::argument('member', Member::class))];
    }

    public function testThrowExceptionWhenMemberOrganizationParamIsMissing(): void
    {
        $resolver = new MemberValueResolver($this->organizationResolver());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing organization parameter in request');
        [...$resolver->resolve(new Request([], [], ['member' => 'f949bd57-3e25-468b-b542-1931781cbf91']), self::argument('member', Member::class))];
    }

    public function testResolveMember(): void
    {
        $organization = OrganizationMother::withMember(
            $member = new Member($memberId = '9a1c9f23-23bf-4dc0-8d10-03848867d7f4', 'email', 'owner')
        );
        $resolver = new MemberValueResolver($this->organizationResolver($organization));
        $request = new Request([], [], ['organization' => 'repman', 'member' => $memberId]);

        self::assertEquals([$member], [...$resolver->resolve($request, self::argument('member', Member::class))]);
        self::assertEquals($member, $request->attributes->get('member'));
    }

    public function testThrowNotFoundExceptionWhenNoMember(): void
    {
        $resolver = new MemberValueResolver($this->organizationResolver());

        $this->expectException(NotFoundHttpException::class);
        [...$resolver->resolve(new Request([], [], ['organization' => OrganizationMother::some(), 'member' => 'invalid']), self::argument('member', Member::class))];
    }

    public function testResolveToken(): void
    {
        $token = new Token('secret', 'name', new \DateTimeImmutable(), null);
        $query = $this->createMock(OrganizationQuery::class);
        $query->method('getByAlias')->willReturn(Option::some(OrganizationMother::some()));
        $query->expects($this->once())->method('findToken')->with(OrganizationMother::some()->id(), 'secret')->willReturn(Option::some($token));
        $resolver = new TokenValueResolver($query, new OrganizationValueResolver($query));

        self::assertSame([$token], [...$resolver->resolve(new Request([], [], ['organization' => 'repman', 'token' => 'secret']), self::argument('token', Token::class))]);
    }

    public function testThrowNotFoundWhenTokenDoesNotExist(): void
    {
        $query = self::createStub(OrganizationQuery::class);
        $query->method('findToken')->willReturn(Option::none());
        $resolver = new TokenValueResolver($query, $this->organizationResolver());

        $this->expectException(NotFoundHttpException::class);
        [...$resolver->resolve(new Request([], [], ['organization' => OrganizationMother::some(), 'token' => 'missing']), self::argument('token', Token::class))];
    }

    private function organizationResolver(?Organization $organization = null): OrganizationValueResolver
    {
        $query = self::createStub(OrganizationQuery::class);
        $query->method('getByAlias')->willReturn(Option::some($organization ?? OrganizationMother::some()));

        return new OrganizationValueResolver($query);
    }

    private function package(string $organizationId): Package
    {
        return new Package(self::PACKAGE_ID, $organizationId, 'vcs', 'https://some.url');
    }

    private static function argument(string $name, string $type): ArgumentMetadata
    {
        return new ArgumentMetadata($name, $type, false, false, null);
    }
}
