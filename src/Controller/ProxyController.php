<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Munus\Control\Option;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class ProxyController extends AbstractController
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
                    'dist-url' => $this->router->generate('index', [], RouterInterface::ABSOLUTE_URL).'dists/%package%/%version%/%reference%.%type%',
                    'preferred' => true,
                ],
            ],
            'providers-lazy-url' => '/p/%package%',
        ]);
    }

    /**
     * @Route("/p/{package}", name="package_provider", requirements={"package"="[A-Za-z0-9_.-]+/[A-Za-z0-9_./-]+?"}, methods={"GET"})
     */
    public function provider(string $package): JsonResponse
    {
        return new JsonResponse($this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->providerData($package))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElse(['packages' => []])
        );
    }

    /**
     * @Route("/dists/{package}/{version}/{ref}.{type}",
     *     name="package_dist",
     *     requirements={"package"="[A-Za-z0-9_.-]+/[A-Za-z0-9_./-]+?","ref"="[a-f0-9]*?","type"="zip|tar"},
     *     methods={"GET"})
     */
    public function distribution(string $package, string $version, string $ref, string $type): BinaryFileResponse
    {
        return new BinaryFileResponse($this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->distFilename($package, $version, $ref, $type))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElseThrow(new NotFoundHttpException('This distribution file can not be found or downloaded from origin url.'))
        );
    }

    /**
     * @Route("/packages", name="packages_list", methods={"GET"})
     */
    public function packagesList(): Response
    {
        return $this->render('packages.html.twig', [
            'proxies' => $this->register->all()->fold([], function (array $packages, Proxy $proxy) {
                $packages[$proxy->name()] = $proxy->syncedPackages()->iterator()->toArray();

                return $packages;
            }),
        ]);
    }
}
