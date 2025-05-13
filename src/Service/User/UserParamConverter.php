<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User;

use Buddy\Repman\Query\Admin\Model\User;
use Buddy\Repman\Query\Admin\UserQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UserParamConverter implements ParamConverterInterface
{
    public function __construct(private readonly UserQuery $userQuery)
    {
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === User::class;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        if (null === $id = $request->attributes->get('user')) {
            throw new BadRequestHttpException('Missing user parameter in request');
        }

        $request->attributes->set(
            $configuration->getName(),
            $this
                ->userQuery
                ->getById($id)
                ->getOrElseThrow(new NotFoundHttpException('User not found'))
        );

        return true;
    }
}
