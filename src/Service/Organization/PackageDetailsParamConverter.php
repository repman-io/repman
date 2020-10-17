<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\PackageDetails;
use Buddy\Repman\Query\User\PackageQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PackageDetailsParamConverter implements ParamConverterInterface
{
    private PackageQuery $packageQuery;

    public function __construct(PackageQuery $packageQuery)
    {
        $this->packageQuery = $packageQuery;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === PackageDetails::class;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        if (null === $id = $request->attributes->get('package')) {
            throw new BadRequestHttpException('Missing package parameter in request');
        }

        /** @var PackageDetails $package */
        $package = $this->packageQuery->getDetailsById($id)->getOrElseThrow(new NotFoundHttpException('Package not found'));
        $organization = $request->attributes->get('organization');
        if ($organization instanceof Organization && $package->organizationId() !== $organization->id()) {
            throw new NotFoundHttpException('Package not found');
        }

        $request->attributes->set($configuration->getName(), $package);

        return true;
    }
}
