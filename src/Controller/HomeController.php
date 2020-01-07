<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class HomeController extends AbstractController
{
    public function home(): Response
    {
        return $this->render('home.html.twig', ['indexUrl' => $this->generateUrl('index', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
    }
}
