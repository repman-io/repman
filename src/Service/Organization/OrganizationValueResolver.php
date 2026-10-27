<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class OrganizationValueResolver implements ValueResolverInterface
{
    private OrganizationQuery $organizationQuery;

    public function __construct(OrganizationQuery $organizationQuery)
    {
        $this->organizationQuery = $organizationQuery;
    }

    /**
     * @return iterable<Organization>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Organization::class) {
            return [];
        }

        return [$this->fromRequest($request, $argument->getName())];
    }

    /**
     * Resolves the organization alias held in a request attribute and keeps the result in that
     * attribute, so the resolvers of the other arguments see the organization regardless of the
     * order of the controller arguments.
     */
    public function fromRequest(Request $request, string $attribute = 'organization'): Organization
    {
        $alias = $request->attributes->get($attribute);
        if ($alias instanceof Organization) {
            return $alias;
        }

        if (!\is_string($alias)) {
            throw new BadRequestHttpException('Missing organization parameter in request');
        }

        $organization = $this->organizationQuery->getByAlias($alias)
            ->getOrElseThrow(new NotFoundHttpException(sprintf('Organization %s not found', $alias)));
        $request->attributes->set($attribute, $organization);

        return $organization;
    }

    /**
     * The organization of the request, if its route has one.
     */
    public function optionalFromRequest(Request $request): ?Organization
    {
        return $request->attributes->has('organization') ? $this->fromRequest($request) : null;
    }
}
