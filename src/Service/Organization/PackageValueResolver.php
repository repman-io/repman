<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\PackageQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PackageValueResolver implements ValueResolverInterface
{
    private PackageQuery $packageQuery;
    private OrganizationValueResolver $organizationResolver;

    public function __construct(PackageQuery $packageQuery, OrganizationValueResolver $organizationResolver)
    {
        $this->packageQuery = $packageQuery;
        $this->organizationResolver = $organizationResolver;
    }

    /**
     * @return iterable<Package>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Package::class) {
            return [];
        }

        $id = $request->attributes->get('package');
        if ($id instanceof Package) {
            return [$id];
        }

        if (!\is_string($id)) {
            throw new BadRequestHttpException('Missing package parameter in request');
        }

        /** @var Package $package */
        $package = $this->packageQuery->getById($id)->getOrElseThrow(new NotFoundHttpException('Package not found'));
        $organization = $this->organizationResolver->optionalFromRequest($request);
        if ($organization !== null && $package->organizationId() !== $organization->id()) {
            throw new NotFoundHttpException('Package not found');
        }

        $request->attributes->set($argument->getName(), $package);

        return [$package];
    }
}
