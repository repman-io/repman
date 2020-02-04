<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class OrganizationParamConverter implements ParamConverterInterface
{
    private OrganizationQuery $organizationQuery;

    public function __construct(OrganizationQuery $organizationQuery)
    {
        $this->organizationQuery = $organizationQuery;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === Organization::class;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        if (null === $alias = $request->attributes->get('organization')) {
            throw new BadRequestHttpException('Missing organization parameter in request');
        }
        $request->attributes->set(
            $configuration->getName(),
            $this->organizationQuery->getByAlias($alias)->getOrElseThrow(new NotFoundHttpException(sprintf('Organization %s not found', $alias)))
        );

        return true;
    }
}
