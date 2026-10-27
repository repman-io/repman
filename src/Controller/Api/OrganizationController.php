<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Api;

use Buddy\Repman\Form\Type\Api\CreateOrganizationType;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Query\Api\Model\Errors;
use Buddy\Repman\Query\Api\Model\Organization;
use Buddy\Repman\Query\Api\Model\Organizations;
use Buddy\Repman\Query\Api\OrganizationQuery;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrganizationController extends ApiController
{
    private OrganizationQuery $organizationQuery;
    private MessageBusInterface $messageBus;

    public function __construct(
        OrganizationQuery $organizationQuery,
        MessageBusInterface $messageBus,
    ) {
        $this->organizationQuery = $organizationQuery;
        $this->messageBus = $messageBus;
    }

    /**
     * List user's organizations.
     */
    #[OA\Parameter(name: 'page', in: 'query')]
    #[Route(path: '/api/organization', name: 'api_organizations', methods: ['GET'])]
    #[OA\Response(response: 200, description: "Returns list of user's organizations", content: new OA\JsonContent(ref: new Model(type: Organizations::class)))]
    #[OA\Tag(name: 'Organization')]
    public function organizations(Request $request): JsonResponse
    {
        return $this->json(
            new Organizations(...$this->paginate(
                fn ($perPage, $offset) => $this->organizationQuery->getUserOrganizations($this->getUser()->id(), $perPage, $offset),
                $this->organizationQuery->userOrganizationsCount($this->getUser()->id()),
                20,
                (int) $request->query->get('page', 1),
                $this->generateUrl('api_organizations', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ))
        );
    }

    /**
     * Create a new organization.
     */
    #[Route(path: '/api/organization', name: 'api_organization_create', methods: ['POST'])]
    #[OA\RequestBody(content: new Model(type: CreateOrganizationType::class))]
    #[OA\Response(response: 201, description: 'Create a new organization', content: new OA\JsonContent(ref: new Model(type: Organization::class)))]
    #[OA\Response(response: 400, description: 'Bad request', content: new OA\JsonContent(ref: new Model(type: Errors::class)))]
    #[OA\Tag(name: 'Organization')]
    public function createOrganization(Request $request): JsonResponse
    {
        $form = $this->createApiForm(CreateOrganizationType::class);
        $form->submit($this->parseJson($request));

        if (!$form->isValid()) {
            return $this->badRequest($this->getErrors($form));
        }

        $this->messageBus->dispatch(new CreateOrganization(
            $id = Uuid::uuid4()->toString(),
            $this->getUser()->id(),
            $form->get('name')->getData()
        ));

        return $this->created(
            $this->organizationQuery
                ->getById($id)
                ->get()
        );
    }
}
