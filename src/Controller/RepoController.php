<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Service\Proxy\ProxyRegister;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class RepoController extends AbstractController
{
    private ProxyRegister $register;

    public function __construct(ProxyRegister $register)
    {
        $this->register = $register;
    }

    /**
     * @Route("/repo/packages.json", name="repo_packages", methods={"GET"})
     */
    public function packages(): JsonResponse
    {
        return new JsonResponse([
            'notify-batch' => 'https://packagist.org/downloads/',
            'providers-url' => '/p/%package%$%hash%.json',
            'metadata-url' => '/p2/%package%.json',
            'search' => 'https://packagist.org/search.json?q=%query%&type=%type%',
            'mirrors' => [
                [
                    'dist-url' => $this->generateUrl('index', [], RouterInterface::ABSOLUTE_URL).'dists/%package%/%version%/%reference%.%type%',
                    'preferred' => true,
                ],
            ],
            'providers-lazy-url' => '/p/%package%',
        ]);
    }
}
