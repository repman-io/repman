<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Api;

use Buddy\Repman\Query\Api\Model\Package;
use Buddy\Repman\Query\Api\PackageQuery;
use Buddy\Repman\Service\Organization\OrganizationValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
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

        /** @var Package $package */
        $package = $this->packageQuery
            ->getById($this->organizationResolver->fromRequest($request)->id(), (string) $request->attributes->get('package'))
            ->getOrElseThrow(new NotFoundHttpException());
        $request->attributes->set($argument->getName(), $package);

        return [$package];
    }
}
