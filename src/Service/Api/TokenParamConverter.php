<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Api;

use Buddy\Repman\Query\Api\Model\Token;
use Buddy\Repman\Query\Api\OrganizationQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TokenParamConverter implements ParamConverterInterface
{
    private OrganizationQuery $organizationQuery;

    public function __construct(OrganizationQuery $organizationQuery)
    {
        $this->organizationQuery = $organizationQuery;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === Token::class;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        /** @var Token $token */
        $token = $this->organizationQuery->findToken(
            $request->attributes->get('organization')->id(),
            $request->attributes->get('token')
        )->getOrElseThrow(new NotFoundHttpException());

        $request->attributes->set($configuration->getName(), $token);

        return true;
    }
}
