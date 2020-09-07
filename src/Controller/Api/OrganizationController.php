<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Api;

use Buddy\Repman\Form\Type\Organization\CreateType;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Query\User\UserQuery;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrganizationController extends ApiController
{
    private UserQuery $userQuery;
    private OrganizationQuery $organizationQuery;

    public function __construct(UserQuery $userQuery, OrganizationQuery $organizationQuery)
    {
        $this->userQuery = $userQuery;
        $this->organizationQuery = $organizationQuery;
    }

    /**
     * @Route("/api/organization",
     *     name="api_organizations",
     *     methods={"GET"})
     */
    public function organizations(Request $request): JsonResponse
    {
        return $this->json($this->paginate(
            fn ($perPage, $offset) => $this->userQuery->getAllOrganizations($this->getUser()->id(), $perPage, $offset),
            $this->userQuery->organizationsCount($this->getUser()->id()),
            20,
            (int) $request->get('page', 1),
            $this->generateUrl('api_organizations', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ));
    }

    /**
     * @Route("/api/organization",
     *     name="api_organization_create",
     *     methods={"POST"})
     */
    public function createOrganization(Request $request): JsonResponse
    {
        $form = $this->createApiForm(CreateType::class);
        $form->submit($this->parseJson($request));

        if (!$form->isValid()) {
            return $this->renderFormErrors($form);
        }

        $this->dispatchMessage(new CreateOrganization(
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
