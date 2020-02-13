<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Service\Organization\PackageManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class RepoController extends AbstractController
{
    private PackageQuery $packageQuery;
    private PackageManager $packageManager;

    public function __construct(PackageQuery $packageQuery, PackageManager $packageManager)
    {
        $this->packageQuery = $packageQuery;
        $this->packageManager = $packageManager;
    }

    /**
     * @Route("/packages.json", host="{organization}.repo.{domain}", name="repo_packages", methods={"GET"}, defaults={"domain":"%domain%"}, requirements={"domain"="%domain%"})
     */
    public function packages(Organization $organization): JsonResponse
    {
        return new JsonResponse([
            'packages' => $this->packageManager->findProviders($organization->alias(), $this->packageQuery->findAll($organization->id(), 99999)),
            'notify-batch' => 'https://packagist.org/downloads/',
            'search' => 'https://packagist.org/search.json?q=%query%&type=%type%',
            'mirrors' => [
                [
                    'dist-url' => $this->generateUrl(
                        'organization_repo_url',
                        ['organization' => $organization->alias()],
                        RouterInterface::ABSOLUTE_URL
                    ).'dists/%package%/%version%/%reference%.%type%',
                    'preferred' => true,
                ],
            ],
        ]);
    }

    /**
     * @Route("/dists/{package}/{version}/{ref}.{type}",
     *     name="repo_package_dist",
     *     host="{organization}.repo.{domain}",
     *     defaults={"domain":"%domain%"},
     *     requirements={"package"="%package_name_pattern%","ref"="[a-f0-9]*?","type"="zip|tar","domain"="%domain%"},
     *     methods={"GET"})
     */
    public function distribution(Organization $organization, string $package, string $version, string $ref, string $type): BinaryFileResponse
    {
        return new BinaryFileResponse($this->packageManager
            ->distFilename($organization->alias(), $package, $version, $ref, $type)
            ->getOrElseThrow(new NotFoundHttpException('This distribution file can not be found or downloaded from origin url.'))
        );
    }
}
