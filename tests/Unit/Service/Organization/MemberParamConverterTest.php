<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Member;
use Buddy\Repman\Service\Organization\MemberParamConverter;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MemberParamConverterTest extends TestCase
{
    public function testThrowExceptionWhenMemberParamIsMissing(): void
    {
        $converter = new MemberParamConverter();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing member parameter in request');
        $converter->apply(new Request(), new ParamConverter(['name' => 'member']));
    }

    public function testThrowExceptionWhenOrganizationParamIsMissing(): void
    {
        $converter = new MemberParamConverter();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing organization parameter in request');
        $converter->apply(new Request([], [], ['member' => 'f949bd57-3e25-468b-b542-1931781cbf91']), new ParamConverter(['name' => 'member']));
    }

    public function testCheckIfMemberIsAlreadyConverted(): void
    {
        $member = new Member('9a1c9f23-23bf-4dc0-8d10-03848867d7f4', 'email', 'owner');

        $converter = new MemberParamConverter();
        $converter->apply($request = new Request([], [], [
            'organization' => new Organization(
                '10b86f64-ccf5-4ef8-a99f-b7cafe1fcf37',
                'Buddy',
                'buddy',
                [new Member('0f143a74-6e9a-4347-a916-b97a4568c0eb', 'email', 'owner')]
            ),
            'member' => $member,
        ]), new ParamConverter(['name' => 'member']));

        self::assertEquals($member, $request->attributes->get('member'));
    }

    public function testConvertMember(): void
    {
        $organization = new Organization(
            '10b86f64-ccf5-4ef8-a99f-b7cafe1fcf37',
            'Buddy',
            'buddy',
            [$member = new Member($memberId = '9a1c9f23-23bf-4dc0-8d10-03848867d7f4', 'email', 'owner')]
        );

        $converter = new MemberParamConverter();
        $converter->apply($request = new Request([], [], ['organization' => $organization, 'member' => $memberId]), new ParamConverter(['name' => 'member']));

        self::assertEquals($member, $request->attributes->get('member'));
    }

    public function testThrowNotFoundExcetpionWhenNoMember(): void
    {
        $organization = new Organization(
            '10b86f64-ccf5-4ef8-a99f-b7cafe1fcf37',
            'Buddy',
            'buddy',
            [$member = new Member($memberId = '9a1c9f23-23bf-4dc0-8d10-03848867d7f4', 'email', 'owner')]
        );

        $this->expectException(NotFoundHttpException::class);

        $converter = new MemberParamConverter();
        $converter->apply($request = new Request([], [], ['organization' => $organization, 'member' => 'invalid']), new ParamConverter(['name' => 'member']));
    }
}
