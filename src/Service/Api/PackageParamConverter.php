<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Api;

use Buddy\Repman\Query\Api\Model\Package;
use Buddy\Repman\Query\Api\PackageQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PackageParamConverter implements ParamConverterInterface
{
    public function __construct(private readonly PackageQuery $packageQuery)
    {
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === Package::class;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        /** @var Package $package */
        $package = $this->packageQuery
            ->getById($request->attributes->get('organization')->id(), $request->attributes->get('package'))
            ->getOrElseThrow(new NotFoundHttpException());

        $request->attributes->set($configuration->getName(), $package);

        return true;
    }
}
