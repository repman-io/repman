<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

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
            'users' => $this->userQuery->findAll(20, $request->get('offset', 0)),
            'count' => $this->userQuery->count(),
        ]);
    }
}
