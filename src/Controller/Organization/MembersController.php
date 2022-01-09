<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Organization;

use Buddy\Repman\Form\Type\Organization\InviteMemberType;
use Buddy\Repman\Form\Type\Organization\Member\ChangeRoleType;
use Buddy\Repman\Message\Organization\Member\AcceptInvitation;
use Buddy\Repman\Message\Organization\Member\ChangeRole;
use Buddy\Repman\Message\Organization\Member\InviteUser;
use Buddy\Repman\Message\Organization\Member\RemoveInvitation;
use Buddy\Repman\Message\Organization\Member\RemoveMember;
use Buddy\Repman\Query\Filter;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Member;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Security\Model\User;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class MembersController extends AbstractController
{
    private OrganizationQuery $organizations;
    private TokenStorageInterface $tokenStorage;
    private MessageBusInterface $messageBus;

    public function __construct(
        OrganizationQuery $organizations,
        TokenStorageInterface $tokenStorage,
        MessageBusInterface $messageBus
    ) {
        $this->organizations = $organizations;
        $this->tokenStorage = $tokenStorage;
        $this->messageBus = $messageBus;
    }

    /**
     * @Route("/organization/{organization}/member", name="organization_members", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function listMembers(Organization $organization, Request $request): Response
    {
        $filter = Filter::fromRequest($request);

        return $this->render('organization/member/members.html.twig', [
            'organization' => $organization,
            'members' => $this->organizations->findAllMembers($organization->id(), $filter),
            'count' => $this->organizations->membersCount($organization->id()),
            'invitations' => $this->organizations->invitationsCount($organization->id()),
            'filter' => $filter,
        ]);
    }

    /**
     * @Route("/user/invitation/{token}", name="organization_accept_invitation", methods={"GET"}, requirements={"token"="%uuid_pattern%"})
     */
    public function acceptInvitation(Request $request, string $token): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (null === $user) {
            $request->getSession()->set('organization-token', $token);

            $this->addFlash('info', 'You need to sign in or sign up to be able to accept this invitation.');

            return $this->redirectToRoute('app_login');
        }

        $organization = $this->organizations->getByInvitation($token, $user->email());
        if ($organization->isEmpty()) {
            $this->addFlash('danger', 'Invitation not found or belongs to different user');
            $this->tokenStorage->setToken();

            return $this->redirectToRoute('app_login');
        }

        $this->messageBus->dispatch(new AcceptInvitation($token, $user->id()));
        $request->getSession()->remove('organization-token');
        $this->addFlash('success', sprintf('The invitation to %s organization has been accepted', $organization->get()->name()));

        return $this->redirectToRoute('organization_overview', ['organization' => $organization->get()->alias()]);
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}/member/invite", name="organization_invite_member", methods={"GET", "POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function invite(Organization $organization, Request $request): Response
    {
        $form = $this->createForm(InviteMemberType::class, [], ['organizationId' => $organization->id()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->messageBus->dispatch(new InviteUser(
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
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}/invitation", name="organization_invitations", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function listInvitations(Organization $organization, Request $request): Response
    {
        $filter = Filter::fromRequest($request);

        return $this->render('organization/member/invitations.html.twig', [
            'organization' => $organization,
            'invitations' => $this->organizations->findAllInvitations($organization->id(), $filter),
            'count' => $this->organizations->invitationsCount($organization->id()),
            'filter' => $filter,
        ]);
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}/invitation/{token}", name="organization_remove_invitation", methods={"DELETE"}, requirements={"organization"="%organization_pattern%"})
     */
    public function removeInvitation(Organization $organization, string $token): Response
    {
        $this->messageBus->dispatch(new RemoveInvitation($organization->id(), $token));
        $this->addFlash('success', 'The invitation has been deleted');

        return $this->redirectToRoute('organization_invitations', ['organization' => $organization->alias()]);
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}/member/{member}", name="organization_remove_member", methods={"DELETE"}, requirements={"organization"="%organization_pattern%", "member"="%uuid_pattern%"})
     */
    public function removeMember(Organization $organization, Member $member): Response
    {
        if ($organization->isLastOwner($member->userId())) {
            $this->addFlash('danger', sprintf('Member "%s" cannot be removed. Organisation must have at least one owner.', $member->email()));
        } else {
            $this->messageBus->dispatch(new RemoveMember($organization->id(), $member->userId()));
            $this->addFlash('success', sprintf('Member "%s" has been removed from organization', $member->email()));
        }

        return $this->redirectToRoute('organization_members', ['organization' => $organization->alias()]);
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}/member/{member}/role", name="organization_change_member_role", methods={"GET", "POST"}, requirements={"organization"="%organization_pattern%", "member"="%uuid_pattern%"})
     */
    public function changeRole(Organization $organization, Member $member, Request $request): Response
    {
        $form = $this->createForm(ChangeRoleType::class, ['role' => $member->role()], ['isLastOwner' => $organization->isLastOwner($member->userId())]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->messageBus->dispatch(new ChangeRole(
                $organization->id(),
                $member->userId(),
                $form->get('role')->getData()
            ));

            $this->addFlash('success', sprintf('Member "%s" role has been successfully changed.', $member->email()));

            return $this->redirectToRoute('organization_members', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/member/changeRole.twig', [
            'organization' => $organization,
            'member' => $member,
            'form' => $form->createView(),
        ]);
    }
}
