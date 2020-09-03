<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Api;

use Buddy\Repman\Form\Type\Organization\GenerateTokenType;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\Organization\RegenerateToken;
use Buddy\Repman\Message\Organization\RemoveToken;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TokenController extends ApiController
{
    private OrganizationQuery $organizationQuery;

    public function __construct(OrganizationQuery $organizationQuery)
    {
        $this->organizationQuery = $organizationQuery;
    }

    /**
     * @Route("/api/{organization}/token",
     *     name="api_tokens",
     *     methods={"GET"}),
     *     requirements={"organization"="%organization_pattern%"})
     */
    public function tokens(Organization $organization, Request $request): JsonResponse
    {
        return $this->json($this->paginate(
            fn ($perPage, $offset) => $this->organizationQuery->findAllTokens($organization->id(), $perPage, $offset),
            $this->organizationQuery->tokenCount($organization->id()),
            20,
            (int) $request->get('page', 1),
            $this->generateUrl('api_tokens', [
                'organization' => $organization->alias(),
            ], UrlGeneratorInterface::ABSOLUTE_URL)
        ));
    }

    /**
     * @Route("/api/{organization}/token",
     *     name="api_token_generate",
     *     methods={"POST"}),
     *     requirements={"organization"="%organization_pattern%"})
     */
    public function generateToken(Organization $organization, Request $request): JsonResponse
    {
        $form = $this->createApiForm(GenerateTokenType::class);
        $form->submit($this->parseJson($request));

        if (!$form->isValid()) {
            return $this->renderFormErrors($form);
        }

        $this->dispatchMessage(new GenerateToken(
            $organization->id(),
            $name = $form->get('name')->getData()
        ));

        return $this->created(
            $this->organizationQuery
                ->findTokenByName($organization->id(), $name)
                ->get()
        );
    }

    /**
     * @Route("/api/{organization}/token/{token}",
     *     name="api_token_remove",
     *     methods={"DELETE"}),
     *     requirements={"organization"="%organization_pattern%"})
     */
    public function removeToken(Organization $organization, string $token): JsonResponse
    {
        if ($this->organizationQuery->findToken($organization->id(), $token)->isEmpty()) {
            return $this->notFound();
        }

        $this->dispatchMessage(new RemoveToken($organization->id(), $token));

        return $this->json(null);
    }

    /**
     * @Route("/api/{organization}/token/{token}",
     *     name="api_token_regenerate",
     *     methods={"PUT"})
     *     requirements={"organization"="%organization_pattern%"})
     */
    public function regenerateToken(Organization $organization, string $token): JsonResponse
    {
        if ($this->organizationQuery->findToken($organization->id(), $token)->isEmpty()) {
            return $this->notFound();
        }

        $this->dispatchMessage(new RegenerateToken($organization->id(), $token));

        return $this->json(null);
    }
}
