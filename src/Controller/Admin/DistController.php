<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

use Buddy\Repman\Message\Proxy\RemoveDist;
use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DistController extends AbstractController
{
    private ProxyRegister $register;

    public function __construct(ProxyRegister $register)
    {
        $this->register = $register;
    }

    /**
     * @Route("/admin/dist", name="admin_dist_list", methods={"GET"})
     */
    public function list(): Response
    {
        return $this->render('admin/dist.html.twig', [
            'proxies' => $this->register->all()->fold([], function (array $packages, Proxy $proxy) {
                $packages[$proxy->name()] = $proxy->syncedPackages()->iterator()->toArray();

                return $packages;
            }),
        ]);
    }

    /**
     * @Route("/admin/dist/{packageName}", name="admin_dist_remove", requirements={"packageName":"%package_name_pattern%"}, methods={"DELETE"})
     */
    public function remove(string $packageName): Response
    {
        $this->dispatchMessage(new RemoveDist($packageName));

        $this->addFlash('success', sprintf('Dist files for package %s will be removed.', $packageName));

        return new RedirectResponse($this->generateUrl('admin_dist_list'));
    }
}
