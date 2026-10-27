<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization\Member;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MemberValueResolver implements ValueResolverInterface
{
    private OrganizationValueResolver $organizationResolver;

    public function __construct(OrganizationValueResolver $organizationResolver)
    {
        $this->organizationResolver = $organizationResolver;
    }

    /**
     * @return iterable<Member>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Member::class) {
            return [];
        }

        $userId = $request->attributes->get($argument->getName());
        if ($userId instanceof Member) {
            return [$userId];
        }

        if (!\is_string($userId)) {
            throw new BadRequestHttpException('Missing member parameter in request');
        }

        $organization = $this->organizationResolver->fromRequest($request);
        $member = $organization->getMember($userId)->getOrElseThrow(
            new NotFoundHttpException(sprintf('User %s not found in %s organization', $userId, $organization->name()))
        );
        $request->attributes->set($argument->getName(), $member);

        return [$member];
    }
}
