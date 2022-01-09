<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Api;

use Buddy\Repman\Form\Type\Api\GenerateTokenType;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\Organization\RegenerateToken;
use Buddy\Repman\Message\Organization\RemoveToken;
use Buddy\Repman\Query\Api\Model\Errors;
use Buddy\Repman\Query\Api\Model\Token;
use Buddy\Repman\Query\Api\Model\Tokens;
use Buddy\Repman\Query\Api\OrganizationQuery;
use Buddy\Repman\Query\User\Model\Organization;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TokenController extends ApiController
{
    private OrganizationQuery $organizationQuery;
    private MessageBusInterface $messageBus;

    public function __construct(
        OrganizationQuery $organizationQuery,
        MessageBusInterface $messageBus
    ) {
        $this->organizationQuery = $organizationQuery;
        $this->messageBus = $messageBus;
    }

    /**
     * List organization's tokens.
     *
     * @Route("/api/organization/{organization}/token",
     *     name="api_tokens",
     *     methods={"GET"},
     *     requirements={"organization"="%organization_pattern%"}
     * )
     *
     * @Oa\Parameter(
     *     name="page",
     *     in="query"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns list of organization's tokens",
     *     @OA\JsonContent(
     *        ref=@Model(type=Tokens::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=403,
     *     description="Forbidden"
     * )
     *
     * @OA\Tag(name="Token")
     */
    public function tokens(Organization $organization, Request $request): JsonResponse
    {
        return $this->json(
            new Tokens(...$this->paginate(
                fn ($perPage, $offset) => $this->organizationQuery->findAllTokens($organization->id(), $perPage, $offset),
                $this->organizationQuery->tokenCount($organization->id()),
                20,
                (int) $request->get('page', 1),
                $this->generateUrl('api_tokens', [
                    'organization' => $organization->alias(),
                ], UrlGeneratorInterface::ABSOLUTE_URL)
            ))
        );
    }

    /**
     * Generate new token.
     *
     * @Route("/api/organization/{organization}/token",
     *     name="api_token_generate",
     *     methods={"POST"},
     *     requirements={"organization"="%organization_pattern%"}
     * )
     *
     * @OA\RequestBody(
     *     @Model(type=GenerateTokenType::class)
     * )
     *
     * @OA\Response(
     *     response=201,
     *     description="Returns generated token",
     *     @OA\JsonContent(
     *        ref=@Model(type=Token::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=400,
     *     description="Bad request",
     *     @OA\JsonContent(
     *        ref=@Model(type=Errors::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=403,
     *     description="Forbidden"
     * )
     *
     * @OA\Tag(name="Token")
     */
    public function generateToken(Organization $organization, Request $request): JsonResponse
    {
        $form = $this->createApiForm(GenerateTokenType::class);
        $form->submit($this->parseJson($request));

        if (!$form->isValid()) {
            return $this->badRequest($this->getErrors($form));
        }

        $this->messageBus->dispatch(new GenerateToken(
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
     * Delete token.
     *
     * @Route("/api/organization/{organization}/token/{token}",
     *     name="api_token_remove",
     *     methods={"DELETE"},
     *     requirements={"organization"="%organization_pattern%"}
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Token deleted"
     * )
     *
     * @OA\Response(
     *     response=404,
     *     description="Token not found"
     * )
     *
     * @OA\Response(
     *     response=403,
     *     description="Forbidden"
     * )
     *
     * @OA\Tag(name="Token")
     */
    public function removeToken(Organization $organization, Token $token): JsonResponse
    {
        $this->messageBus->dispatch(new RemoveToken($organization->id(), $token->getValue()));

        return new JsonResponse();
    }

    /**
     * Regenerate token.
     *
     * @Route("/api/organization/{organization}/token/{token}",
     *     name="api_token_regenerate",
     *     methods={"PUT"})
     *     requirements={"organization"="%organization_pattern%"}
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Token regenerated"
     * )
     *
     * @OA\Response(
     *     response=404,
     *     description="Token not found"
     * )
     *
     * @OA\Response(
     *     response=403,
     *     description="Forbidden"
     * )
     *
     * @OA\Tag(name="Token")
     */
    public function regenerateToken(Organization $organization, Token $token): JsonResponse
    {
        $this->messageBus->dispatch(new RegenerateToken($organization->id(), $token->getValue()));

        return $this->json(
            $this->organizationQuery
                ->findTokenByName($organization->id(), $token->getName())
                ->get()
        );
    }
}
