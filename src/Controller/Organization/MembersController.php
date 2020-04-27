<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Organization;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Form\Type\Organization\InviteMemberType;
use Buddy\Repman\Message\Organization\Member\AcceptInvitation;
use Buddy\Repman\Message\Organization\Member\InviteUser;
use Buddy\Repman\Message\Organization\Member\RemoveInvitation;
use Buddy\Repman\Message\Organization\Member\RemoveMember;
use Buddy\Repman\Query\Admin\Model\User as UserReadModel;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Invitation;
use Buddy\Repman\Query\User\OrganizationQuery;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class MembersController extends AbstractController
{
    private OrganizationQuery $organizations;
    private TokenStorageInterface $tokenStorage;

    public function __construct(OrganizationQuery $organizations, TokenStorageInterface $tokenStorage)
    {
        $this->organizations = $organizations;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Route("/organization/{organization}/member", name="organization_members", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function listMembers(Organization $organization, Request $request): Response
    {
        return $this->render('organization/member/members.html.twig', [
            'organization' => $organization,
            'members' => $this->organizations->findAllMembers($organization->id(), 20, (int) $request->get('offset', 0)),
            'count' => $this->organizations->membersCount($organization->id()),
            'invitations' => $this->organizations->invitationsCount($organization->id()),
        ]);
    }

    /**
     * @Route("/organization/{organization}/member/invite", name="organization_invite_member", methods={"GET", "POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function invite(Organization $organization, Request $request): Response
    {
        $form = $this->createForm(InviteMemberType::class, [], ['organizationId' => $organization->id()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatchMessage(new InviteUser(
                $email = $form->get('email')->getData(),
                $form->get('role')->getData(),
                $organization->id(),
                Uuid::uuid4()->toString()
            ));

            $this->addFlash('success', sprintf('User "%s" has been successfully invited.', $email));

            return $this->redirectToRoute('organization_invitations', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/member/invite.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/organization/{organization}/invitation", name="organization_invitations", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function listInvitations(Organization $organization, Request $request): Response
    {
        return $this->render('organization/member/invitations.html.twig', [
            'organization' => $organization,
            'invitations' => $this->organizations->findAllInvitations($organization->id(), 20, (int) $request->get('offset', 0)),
            'count' => $this->organizations->invitationsCount($organization->id()),
        ]);
    }

    /**
     * @Route("/user/invitation/{token}", name="organization_accept_invitation", methods={"GET"}, requirements={"token"="%uuid_pattern%"})
     */
    public function acceptInvitation(string $token): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $organization = $this->organizations->getByInvitation($token, $user->getEmail());
        if ($organization->isEmpty()) {
            $this->addFlash('danger', 'Invitation not found or belongs to different user');
            $this->tokenStorage->setToken();
            throw new AuthenticationException();
        }

        $this->dispatchMessage(new AcceptInvitation($token, $user->id()->toString()));
        $this->addFlash('success', sprintf('The invitation to %s organization has been accepted', $organization->get()->name()));

        return $this->redirectToRoute('organization_overview', ['organization' => $organization->get()->alias()]);
    }

    /**
     * @Route("/organization/{organization}/invitation/{token}", name="organization_remove_invitation", methods={"DELETE"}, requirements={"organization"="%organization_pattern%"})
     */
    public function removeInvitation(Organization $organization, string $token): Response
    {
        $this->dispatchMessage(new RemoveInvitation($organization->id(), $token));
        $this->addFlash('success', 'The invitation has been deleted');

        return $this->redirectToRoute('organization_invitations', ['organization' => $organization->alias()]);
    }

    /**
     * @Route("/organization/{organization}/member/{user}", name="organization_remove_member", methods={"DELETE"}, requirements={"organization"="%organization_pattern%"})
     */
    public function removeMember(Organization $organization, UserReadModel $user): Response
    {
        $this->dispatchMessage(new RemoveMember($organization->id(), $user->id()));
        $this->addFlash('success', sprintf('Member "%s" has been removed from organization', $user->email()));

        return $this->redirectToRoute('organization_members', ['organization' => $organization->alias()]);
    }
}
