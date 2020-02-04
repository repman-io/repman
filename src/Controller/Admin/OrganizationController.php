<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Query\Admin\OrganizationQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class OrganizationController extends AbstractController
{
    private OrganizationQuery $organizationQuery;

    public function __construct(OrganizationQuery $organizationQuery)
    {
        $this->organizationQuery = $organizationQuery;
    }

    /**
     * @Route("/admin/organization", name="admin_organization_list", methods={"GET"})
     */
    public function list(Request $request): Response
    {
        return $this->render('admin/organization/list.html.twig', [
            'organizations' => $this->organizationQuery->findAll(20, (int) $request->get('offset', 0)),
            'count' => $this->organizationQuery->count(),
        ]);
    }
}
