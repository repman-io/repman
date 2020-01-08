<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\RemoteFilesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class ProxyController
{
    private RouterInterface $router;
    private RemoteFilesystem $remoteFilesystem;

    public function __construct(RouterInterface $router, RemoteFilesystem $remoteFilesystem)
    {
        $this->router = $router;
        $this->remoteFilesystem = $remoteFilesystem;
    }

    /**
     * @Route("/packages.json", name="packages", methods={"GET"})
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
            'providers-lazy-url' => '/repo/packagist/p/%package%',
        ]);
    }

    /**
     * @Route("/repo/{repo}/p/{name}", name="package_provider", requirements={"name"="[A-Za-z0-9_.-]+/[A-Za-z0-9_./-]+?"}, methods={"GET"})
     */
    public function provider(string $repo, string $name): JsonResponse
    {
        $proxy = new Proxy('https://packagist.org', $this->remoteFilesystem);

        return new JsonResponse($proxy->provider($name)->getOrElse([]));
    }
}
