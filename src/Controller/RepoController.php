<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Message\Organization\AddDownload;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Service\Organization\PackageManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
     * @Cache(public=false)
     */
    public function packages(Organization $organization): JsonResponse
    {
        return new JsonResponse([
            'packages' => $this->packageManager->findProviders($organization->alias(), $this->packageQuery->getAllNames($organization->id())),
            'metadata-url' => '/p2/%package%.json',
            'notify-batch' => $this->generateUrl('repo_package_downloads', [
                'organization' => $organization->alias(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
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
     * @Cache(public=false)
     */
    public function distribution(Organization $organization, string $package, string $version, string $ref, string $type): BinaryFileResponse
    {
        return new BinaryFileResponse($this->packageManager
            ->distFilename($organization->alias(), $package, $version, $ref, $type)
            ->getOrElseThrow(new NotFoundHttpException('This distribution file can not be found or downloaded from origin url.'))
        );
    }

    /**
     * @Route("/downloads",
     *     name="repo_package_downloads",
     *     host="{organization}.repo.{domain}",
     *     defaults={"domain":"%domain%"},
     *     requirements={"domain"="%domain%"},
     *     methods={"POST"})
     */
    public function downloads(Request $request, Organization $organization): JsonResponse
    {
        $contents = json_decode($request->getContent(), true);
        if (!isset($contents['downloads']) || !is_array($contents['downloads']) || $contents['downloads'] === []) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid request format, must be a json object containing a downloads key filled with an array of name/version objects',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $packageMap = $this->getPackageNameMap($organization->id());
        foreach ($contents['downloads'] as $package) {
            if (!isset($package['name']) || !isset($package['version'])) {
                continue;
            }

            if (isset($packageMap[$package['name']])) {
                $this->dispatchMessage(new AddDownload(
                    $packageMap[$package['name']],
                    $package['version'],
                    new \DateTimeImmutable(),
                    $request->getClientIp(),
                    $request->headers->get('User-Agent')
                ));
            }
        }

        return new JsonResponse(['status' => 'success'], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/p2/{package}.json",
     *      host="{organization}.repo.{domain}",
     *      name="repo_package_provider_v2",
     *      methods={"GET"},
     *      defaults={"domain":"%domain%"},
     *      requirements={"domain"="%domain%","package"="%package_name_pattern%"})
     * @Cache(public=false)
     */
    public function providerV2(Organization $organization, string $package): JsonResponse
    {
        $providerData = $this->packageManager->findProviders(
            $organization->alias(),
            [new PackageName('', $package)]
        );

        return new JsonResponse($providerData === [] ? new \stdClass() : $providerData);
    }

    /**
     * @return array<string, string>
     */
    private function getPackageNameMap(string $organizationId): array
    {
        $map = [];
        foreach ($this->packageQuery->getAllNames($organizationId) as $package) {
            $map[$package->name()] = $package->id();
        }

        return $map;
    }
}
