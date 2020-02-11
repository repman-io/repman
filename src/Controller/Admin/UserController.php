<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

use Buddy\Repman\Message\User\DisableUser;
use Buddy\Repman\Message\User\EnableUser;
use Buddy\Repman\Query\Admin\Model\User;
use Buddy\Repman\Query\Admin\UserQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class UserController extends AbstractController
{
    private UserQuery $userQuery;

    public function __construct(UserQuery $userQuery)
    {
        $this->userQuery = $userQuery;
    }

    /**
     * @Route("/admin/user", name="admin_user_list", methods={"GET"})
     */
    public function list(Request $request): Response
    {
        return $this->render('admin/user/list.html.twig', [
            'users' => $this->userQuery->findAll(20, (int) $request->get('offset', 0)),
            'count' => $this->userQuery->count(),
        ]);
    }

    /**
     * @Route("/admin/user/{user}/disable", name="admin_user_disable", methods={"POST"}, requirements={"user"="%uuid_pattern%"})
     */
    public function disable(User $user, Request $request): Response
    {
        $this->dispatchMessage(new DisableUser($user->id()));
        $this->addFlash('success', sprintf('User %s has been successfully disabled', $user->email()));

        return $this->redirectToRoute('admin_user_list');
    }

    /**
     * @Route("/admin/user/{user}/enable", name="admin_user_enable", methods={"POST"}, requirements={"user"="%uuid_pattern%"})
     */
    public function enable(User $user, Request $request): Response
    {
        $this->dispatchMessage(new EnableUser($user->id()));
        $this->addFlash('success', sprintf('User %s has been successfully enabled', $user->email()));

        return $this->redirectToRoute('admin_user_list');
    }
}
