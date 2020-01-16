<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DistController extends AbstractController
{
    /**
     * @Route("/admin/dist", name="admin_dist_list", methods={"GET"})
     */
    public function list(): Response
    {
        return $this->render('admin/dist.html.twig');
    }
}
