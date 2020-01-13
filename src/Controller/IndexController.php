<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class IndexController extends AbstractController
{
    /**
     * @Route(path="/", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('index.html.twig', ['indexUrl' => $this->generateUrl('index', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
    }
}
