<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class ProxyController
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @Route("packages.json", name="packages", methods={"GET"})
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
                    'dist-url' => $this->router->generate('index', [], RouterInterface::ABSOLUTE_URL).'repo/packagist/dists/%package%/%version%/%reference%.%type%',
                    'preferred' => true,
                ],
            ],
            'providers-lazy-url' => '/repo/packagist/p/%package%.json',
        ]);
    }
}
