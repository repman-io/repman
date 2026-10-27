<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User;

use Buddy\Repman\Query\Admin\Model\User;
use Buddy\Repman\Query\Admin\UserQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UserValueResolver implements ValueResolverInterface
{
    private UserQuery $userQuery;

    public function __construct(UserQuery $userQuery)
    {
        $this->userQuery = $userQuery;
    }

    /**
     * @return iterable<User>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== User::class) {
            return [];
        }

        $id = $request->attributes->get('user');
        if ($id instanceof User) {
            return [$id];
        }

        if (!\is_string($id)) {
            throw new BadRequestHttpException('Missing user parameter in request');
        }

        $user = $this->userQuery->getById($id)->getOrElseThrow(new NotFoundHttpException('User not found'));
        $request->attributes->set($argument->getName(), $user);

        return [$user];
    }
}
