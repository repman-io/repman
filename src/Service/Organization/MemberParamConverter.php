<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Member;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MemberParamConverter implements ParamConverterInterface
{
    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === Member::class;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        if (null === $userId = $request->attributes->get($configuration->getName())) {
            throw new BadRequestHttpException('Missing member parameter in request');
        }

        $organization = $request->attributes->get('organization');
        if (!$organization instanceof Organization) {
            throw new BadRequestHttpException('Missing organization parameter in request');
        }

        if ($request->attributes->get($configuration->getName()) instanceof Member) {
            return true;
        }

        $request->attributes->set(
            $configuration->getName(),
            $organization->getMember($userId)->getOrElseThrow(new NotFoundHttpException(sprintf('User %s not found in %s organization', $userId, $organization->name())))
        );

        return true;
    }
}
