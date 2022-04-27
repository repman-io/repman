<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

use Buddy\Repman\Security\Model\User;
use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Message\Organization\Member\InviteUser;
use Buddy\Repman\Message\Organization\RemoveOrganization;
use Buddy\Repman\Query\Admin\OrganizationQuery;
use Buddy\Repman\Query\Filter;
use Buddy\Repman\Query\User\Model\Organization;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

final class OrganizationController extends AbstractController
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
     * @Route("/admin/organization", name="admin_organization_list", methods={"GET"})
     */
    public function list(Request $request): Response
    {
        $filter = Filter::fromRequest($request);

        return $this->render('admin/organization/list.html.twig', [
            'organizations' => $this->organizationQuery->findAll($filter),
            'count' => $this->organizationQuery->count(),
            'filter' => $filter,
        ]);
    }

    /**
     * @Route("/admin/organization/{organization}", name="admin_organization_remove", methods={"DELETE"}, requirements={"organization"="%organization_pattern%"})
     */
    public function remove(Organization $organization, Request $request): Response
    {
        $this->messageBus->dispatch(new RemoveOrganization($organization->id()));
        $this->addFlash('success', sprintf('Organization %s has been successfully removed', $organization->name()));

        return $this->redirectToRoute('admin_organization_list');
    }

    /**
     * @Route("/admin/organization/{organization}", name="admin_organization_add_admin", methods={"POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function addAdmin(Organization $organization, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->messageBus->dispatch(new InviteUser(
            $user->email(),
            Member::ROLE_OWNER,
            $organization->id(),
            Uuid::uuid4()->toString()
        ));

        $this->addFlash('success', sprintf('The user %s has been successfully invited for %s', $user->email(), $organization->name()));

        return $this->redirectToRoute('admin_organization_list');
    }

    /**
     * @Route("/admin/stats", name="admin_stats", methods={"GET"})
     */
    public function stats(Request $request): Response
    {
        $days = min(max((int) $request->get('days', 30), 7), 365);

        return $this->render('admin/stats.html.twig', [
            'installs' => $this->organizationQuery->getInstalls($days),
            'days' => $days,
        ]);
    }
}
