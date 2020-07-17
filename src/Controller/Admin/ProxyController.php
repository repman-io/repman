<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

use Buddy\Repman\Message\Proxy\RemoveDist;
use Buddy\Repman\Query\Admin\Proxy\DownloadsQuery;
use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ProxyController extends AbstractController
{
    private ProxyRegister $register;
    private DownloadsQuery $downloadsQuery;

    public function __construct(ProxyRegister $register, DownloadsQuery $downloadsQuery)
    {
        $this->register = $register;
        $this->downloadsQuery = $downloadsQuery;
    }

    /**
     * @Route("/admin/dist/{proxy}", name="admin_dist_list", methods={"GET"})
     */
    public function list(string $proxy, Request $request): Response
    {
        $packages = $this->register->getByHost($proxy)->syncedPackages();
        $count = $packages->length();
        $packages = $packages->drop((int) $request->get('offset', 0))->take(20)->iterator()->toArray();

        return $this->render('admin/proxy/dist.html.twig', [
            'proxy' => $proxy,
            'packages' => $packages,
            'downloads' => $this->downloadsQuery->findByNames($packages),
            'count' => $count,
        ]);
    }

    /**
     * @Route("/admin/proxy/stats", name="admin_proxy_stats")
     */
    public function stats(Request $request): Response
    {
        $days = min(max((int) $request->get('days', 30), 7), 365);

        return $this->render('admin/proxy/stats.html.twig', [
            'installs' => $this->downloadsQuery->getInstalls($days),
            'days' => $days,
        ]);
    }

    /**
     * @Route("/admin/dist/{proxy}/{packageName}", name="admin_dist_remove", requirements={"packageName":"%package_name_pattern%"}, methods={"DELETE"})
     */
    public function remove(string $proxy, string $packageName): Response
    {
        $this->dispatchMessage(new RemoveDist($proxy, $packageName));

        $this->addFlash('success', sprintf('Dist files for package %s will be removed.', $packageName));

        return new RedirectResponse($this->generateUrl('admin_dist_list', ['proxy' => $proxy]));
    }
}
