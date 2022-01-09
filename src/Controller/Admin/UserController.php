<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

use Buddy\Repman\Form\Type\User\ChangeRolesType;
use Buddy\Repman\Message\User\ChangeRoles;
use Buddy\Repman\Message\User\DisableUser;
use Buddy\Repman\Message\User\EnableUser;
use Buddy\Repman\Query\Admin\Model\User;
use Buddy\Repman\Query\Admin\UserQuery;
use Buddy\Repman\Query\Filter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

final class UserController extends AbstractController
{
    private UserQuery $userQuery;
    private MessageBusInterface $messageBus;

    public function __construct(
        UserQuery $userQuery,
        MessageBusInterface $messageBus
    ) {
        $this->userQuery = $userQuery;
        $this->messageBus = $messageBus;
    }

    /**
     * @Route("/admin/user", name="admin_user_list", methods={"GET"})
     */
    public function list(Request $request): Response
    {
        $filter = Filter::fromRequest($request);

        return $this->render('admin/user/list.html.twig', [
            'users' => $this->userQuery->findAll($filter),
            'count' => $this->userQuery->count(),
            'filter' => $filter,
        ]);
    }

    /**
     * @Route("/admin/user/{user}/disable", name="admin_user_disable", methods={"POST"}, requirements={"user"="%uuid_pattern%"})
     */
    public function disable(User $user, Request $request): Response
    {
        $this->messageBus->dispatch(new DisableUser($user->id()));
        $this->addFlash('success', sprintf('User %s has been successfully disabled', $user->email()));

        return $this->redirectToRoute('admin_user_list');
    }

    /**
     * @Route("/admin/user/{user}/enable", name="admin_user_enable", methods={"POST"}, requirements={"user"="%uuid_pattern%"})
     */
    public function enable(User $user, Request $request): Response
    {
        $this->messageBus->dispatch(new EnableUser($user->id()));
        $this->addFlash('success', sprintf('User %s has been successfully enabled', $user->email()));

        return $this->redirectToRoute('admin_user_list');
    }

    /**
     * @Route("/admin/user/{user}/roles", name="admin_user_roles", methods={"POST","GET"}, requirements={"user"="%uuid_pattern%"})
     */
    public function updateRoles(User $user, Request $request): Response
    {
        $form = $this->createForm(ChangeRolesType::class, ['admin' => in_array('ROLE_ADMIN', $user->roles(), true)]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $roles = (bool) $form->get('admin')->getData() ? ['ROLE_ADMIN'] : [];
            $userRoles = array_diff($user->roles(), ['ROLE_ADMIN']);
            $this->messageBus->dispatch(new ChangeRoles($user->id(), array_merge($userRoles, $roles)));
            $this->addFlash('success', sprintf('User %s roles has been successfully changed', $user->email()));

            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('admin/user/changeRoles.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
