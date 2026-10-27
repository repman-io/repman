<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\User;

use Buddy\Repman\Query\Admin\Model\User;
use Buddy\Repman\Query\Admin\UserQuery;
use Buddy\Repman\Service\User\UserValueResolver;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UserValueResolverTest extends TestCase
{
    public function testThrowExceptionWhenUserParamIsMissing(): void
    {
        $resolver = new UserValueResolver(self::createStub(UserQuery::class));

        $this->expectException(BadRequestHttpException::class);
        [...$resolver->resolve(new Request(), self::argument())];
    }

    public function testThrowNotFoundWhenUserDoesNotExist(): void
    {
        $query = self::createStub(UserQuery::class);
        $query->method('getById')->willReturn(Option::none());

        $this->expectException(NotFoundHttpException::class);
        [...(new UserValueResolver($query))->resolve(new Request([], [], ['user' => 'missing']), self::argument())];
    }

    public function testResolveUser(): void
    {
        $user = new User('1b4a0e3e-1a6b-4a39-9e3b-1f9c2b7d7d10', 'test@repman.io', 'enabled', []);
        $query = $this->createMock(UserQuery::class);
        $query->expects($this->once())->method('getById')->with($user->id())->willReturn(Option::some($user));
        $request = new Request([], [], ['user' => $user->id()]);

        self::assertSame([$user], [...(new UserValueResolver($query))->resolve($request, self::argument())]);
        self::assertSame($user, $request->attributes->get('user'));
    }

    public function testIgnoreOtherTypes(): void
    {
        $resolver = new UserValueResolver(self::createStub(UserQuery::class));

        self::assertSame([], [...$resolver->resolve(new Request([], [], ['user' => 'x']), new ArgumentMetadata('user', 'string', false, false, null))]);
    }

    private static function argument(): ArgumentMetadata
    {
        return new ArgumentMetadata('user', User::class, false, false, null);
    }
}
