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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class RepoController extends AbstractController
{
    private PackageQuery $packageQuery;
    private PackageManager $packageManager;
    private MessageBusInterface $messageBus;

    public function __construct(
        PackageQuery $packageQuery,
        PackageManager $packageManager,
        MessageBusInterface $messageBus
    ) {
        $this->packageQuery = $packageQuery;
        $this->packageManager = $packageManager;
        $this->messageBus = $messageBus;
    }

    /**
     * @Route("/packages.json", host="{organization}{sep1}repo{sep2}{domain}", name="repo_packages", methods={"GET"}, defaults={"domain":"%domain%","sep1"="%organization_separator%","sep2"="%domain_separator%"}, requirements={"domain"="%domain%","sep1"="%organization_separator%","sep2"="%domain_separator%"})
     * @Cache(public=false)
     */
    public function packages(Request $request, Organization $organization): JsonResponse
    {
        $packageNames = $this->packageQuery->getAllNames($organization->id());
        [$lastModified, $packages] = $this->packageManager->findProviders($organization->alias(), $packageNames);

        $response = (new JsonResponse([
            'packages' => $packages,
            'available-packages' => array_map(static fn (PackageName $packageName) => $packageName->name(), $packageNames),
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
                [
                    'dist-url' => $this->generateUrl(
                        'organization_repo_url',
                        ['organization' => $organization->alias()],
                        RouterInterface::ABSOLUTE_URL
                    ).'dists/%package%/%version%/%type%',
                    'preferred' => false,
                ],
            ],
        ]))
        ->setPrivate()
        ->setLastModified($lastModified);

        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Route("/dists/{package}/{version}/{ref}.{type}",
     *     name="repo_package_dist",
     *     host="{organization}{sep1}repo{sep2}{domain}",
     *     defaults={"domain":"%domain%","sep1"="%organization_separator%","sep2"="%domain_separator%"},
     *     requirements={"package"="%package_name_pattern%","ref"="[a-f0-9]*?","type"="zip|tar","domain"="%domain%","sep1"="%organization_separator%","sep2"="%domain_separator%"},
     *     methods={"GET"})
     * @Cache(public=false)
     */
    public function distribution(Organization $organization, string $package, string $version, string $ref, string $type): StreamedResponse
    {
        $filename = $this->packageManager
            ->distFilename($organization->alias(), $package, $version, $ref, $type)
            ->getOrElseThrow(new NotFoundHttpException('This distribution file can not be found or downloaded from origin url.'));

        return new StreamedResponse(function () use ($filename): void {
            $outputStream = \fopen('php://output', 'wb');
            if (false === $outputStream) {
                throw new HttpException(500, 'Could not open output stream to send binary file.'); // @codeCoverageIgnore
            }
            $fileStream = $this->packageManager->getDistFileReference($filename);
            \stream_copy_to_stream(
                $fileStream
                    ->getOrElseThrow(new NotFoundHttpException('This distribution file can not be found or downloaded from origin url.')),
                $outputStream
            );
        });
    }

    /**
     * @Route("/dists/{package}/{version}/{type}",
     *     name="repo_artifact_package_dist",
     *     host="{organization}{sep1}repo{sep2}{domain}",
     *     defaults={"domain":"%domain%","sep1"="%organization_separator%","sep2"="%domain_separator%"},
     *     requirements={"package"="%package_name_pattern%","type"="zip|tar","domain"="%domain%","sep1"="%organization_separator%","sep2"="%domain_separator%"},
     *     methods={"GET"})
     * @Cache(public=false)
     */
    public function artifactDistribution(Organization $organization, string $package, string $version, string $type): StreamedResponse
    {
        $filename = $this->packageManager
            ->distFilename($organization->alias(), $package, $version, '', $type)
            ->getOrElseThrow(new NotFoundHttpException('This distribution file can not be found or downloaded from origin url.'));

        return new StreamedResponse(function () use ($filename): void {
            $outputStream = \fopen('php://output', 'wb');
            if (false === $outputStream) {
                throw new HttpException(500, 'Could not open output stream to send binary file.'); // @codeCoverageIgnore
            }
            $fileStream = $this->packageManager->getDistFileReference($filename);
            \stream_copy_to_stream(
                $fileStream
                    ->getOrElseThrow(new NotFoundHttpException('This distribution file can not be found or downloaded from origin url.')),
                $outputStream
            );
        });
    }

    /**
     * @Route("/downloads",
     *     name="repo_package_downloads",
     *     host="{organization}{sep1}repo{sep2}{domain}",
     *     defaults={"domain":"%domain%","sep1"="%organization_separator%","sep2"="%domain_separator%"},
     *     requirements={"domain"="%domain%","sep1"="%organization_separator%","sep2"="%domain_separator%"},
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
                $this->messageBus->dispatch(new AddDownload(
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
     * @Route("/p2/{package}~dev.json",
     *      host="{organization}{sep1}repo{sep2}{domain}",
     *      name="repo_package_provider_v2_dev",
     *      methods={"GET"},
     *      defaults={"domain":"%domain%","sep1"="%organization_separator%","sep2"="%domain_separator%"},
     *      requirements={"domain"="%domain%","package"="%package_name_pattern%","sep1"="%organization_separator%","sep2"="%domain_separator%"})
     * @Cache(public=false)
     */
    public function providerV2Dev(Request $request, Organization $organization, string $package): JsonResponse
    {
        if (($package = preg_replace('/~dev$/', '', $package)) === null) {
            throw new NotFoundHttpException();
        }

        return $this->providerV2($request, $organization, $package);
    }

    /**
     * @Route("/p2/{package}.json",
     *      host="{organization}{sep1}repo{sep2}{domain}",
     *      name="repo_package_provider_v2",
     *      methods={"GET"},
     *      defaults={"domain":"%domain%","sep1"="%organization_separator%","sep2"="%domain_separator%"},
     *      requirements={"domain"="%domain%","package"="%package_name_pattern%","sep1"="%organization_separator%","sep2"="%domain_separator%"})
     * @Cache(public=false)
     */
    public function providerV2(Request $request, Organization $organization, string $package): JsonResponse
    {
        [$lastModified, $providerData] = $this->packageManager->findProviders(
            $organization->alias(),
            [new PackageName('', $package)]
        );

        if ($providerData === []) {
            throw new NotFoundHttpException();
        }

        $response = (new JsonResponse(['packages' => $providerData]))
            ->setLastModified($lastModified)
            ->setPrivate();

        $response->isNotModified($request);

        return $response;
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
