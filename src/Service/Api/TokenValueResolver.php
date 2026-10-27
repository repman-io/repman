<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Api;

use Buddy\Repman\Query\Api\Model\Token;
use Buddy\Repman\Query\Api\OrganizationQuery;
use Buddy\Repman\Service\Organization\OrganizationValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TokenValueResolver implements ValueResolverInterface
{
    private OrganizationQuery $organizationQuery;
    private OrganizationValueResolver $organizationResolver;

    public function __construct(OrganizationQuery $organizationQuery, OrganizationValueResolver $organizationResolver)
    {
        $this->organizationQuery = $organizationQuery;
        $this->organizationResolver = $organizationResolver;
    }

    /**
     * @return iterable<Token>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Token::class) {
            return [];
        }

        /** @var Token $token */
        $token = $this->organizationQuery->findToken(
            $this->organizationResolver->fromRequest($request)->id(),
            (string) $request->attributes->get('token')
        )->getOrElseThrow(new NotFoundHttpException());
        $request->attributes->set($argument->getName(), $token);

        return [$token];
    }
}
