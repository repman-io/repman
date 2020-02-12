<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class IndexController extends AbstractController
{
    /**
     * @Route(path="/", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }
}
