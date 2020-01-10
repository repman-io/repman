<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Munus\Control\Option;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class ProxyController
{
    private RouterInterface $router;
    private ProxyRegister $register;

    public function __construct(RouterInterface $router, ProxyRegister $register)
    {
        $this->router = $router;
        $this->register = $register;
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
     * @Route("/repo/{repo}/p/{package}", name="package_provider", requirements={"package"="[A-Za-z0-9_.-]+/[A-Za-z0-9_./-]+?"}, methods={"GET"})
     */
    public function provider(string $repo, string $package): JsonResponse
    {
        return new JsonResponse($this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->providerData($package))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElse([])
        );
    }

    /**
     * @Route("/repo/{repo}/dists/{package}/{version}/{ref}.{type}",
     *     name="package_dist",
     *     requirements={"package"="[A-Za-z0-9_.-]+/[A-Za-z0-9_./-]+?","ref"="[a-f0-9]*?","type"="zip|tar"},
     *     methods={"GET"})
     */
    public function distribution(string $repo, string $package, string $version, string $ref, string $type): BinaryFileResponse
    {
        return new BinaryFileResponse($this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->distFilename($package, $version, $ref, $type))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElseThrow(new NotFoundHttpException('This distribution file can not be found or downloaded from origin url.'))
        );
    }
}
