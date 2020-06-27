<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Message\Proxy\AddDownloads;
use Buddy\Repman\Message\Proxy\AddDownloads\Package;
use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Munus\Control\Option;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class ProxyController extends AbstractController
{
    private ProxyRegister $register;

    public function __construct(ProxyRegister $register)
    {
        $this->register = $register;
    }

    /**
     * @Route("/packages.json", host="repo.{domain}", name="packages", methods={"GET"}, defaults={"domain"="%domain%"}, requirements={"domain"="%domain%"})
     */
    public function packages(): JsonResponse
    {
        return new JsonResponse([
            'notify-batch' => $this->generateUrl('package_downloads', [], RouterInterface::ABSOLUTE_URL),
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

    /**
     * @Route("/p/{package}", name="package_provider", requirements={"package"="%package_name_pattern%"}, methods={"GET"})
     */
    public function provider(string $package): JsonResponse
    {
        return new JsonResponse($this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->providerData($package))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElse(['packages' => new \stdClass()])
        );
    }

    /**
     * @Route("/p2/{package}.json",
     *     name="package_provider_v2",
     *     host="repo.{domain}",
     *     defaults={"domain"="%domain%"},
     *     requirements={"package"="%package_name_pattern%","domain"="%domain%"},
     *     methods={"GET"})
     */
    public function providerV2(string $package): JsonResponse
    {
        return new JsonResponse($this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->providerDataV2($package))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElse(['packages' => new \stdClass()])
        );
    }

    /**
     * @Route("/dists/{package}/{version}/{ref}.{type}",
     *     name="package_dist",
     *     host="repo.{domain}",
     *     defaults={"domain"="%domain%"},
     *     requirements={"package"="%package_name_pattern%","ref"="[a-f0-9]*?","type"="zip|tar","domain"="%domain%"},
     *     methods={"GET"})
     */
    public function distribution(string $package, string $version, string $ref, string $type): StreamedResponse
    {
        /** @var resource $stream */
        $stream = $this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->distStream($package, $version, $ref, $type))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElseThrow(new NotFoundHttpException('This distribution file can not be found or downloaded from origin url.'));

        $headers = [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'application/zip',
            'Content-Length' => fstat($stream)['size'],
        ];

        return new StreamedResponse(function () use ($stream) {
            $out = fopen('php://output', 'wb');
            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);
        }, 200, $headers);
    }

    /**
     * @Route("/downloads",
     *     name="package_downloads",
     *     host="repo.{domain}",
     *     defaults={"domain":"%domain%"},
     *     requirements={"domain"="%domain%"},
     *     methods={"POST"})
     */
    public function downloads(Request $request): JsonResponse
    {
        $contents = json_decode($request->getContent(), true);
        if (!isset($contents['downloads']) || !is_array($contents['downloads']) || $contents['downloads'] === []) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid request format, must be a json object containing a downloads key filled with an array of name/version objects',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->dispatchMessage(new AddDownloads(
            array_map(function (array $data): Package {
                return new Package($data['name'], $data['version']);
            }, array_filter($contents['downloads'], function (array $row): bool {
                return isset($row['name'], $row['version']);
            })),
            new \DateTimeImmutable(),
            $request->getClientIp(),
            $request->headers->get('User-Agent')
        ));

        return new JsonResponse(['status' => 'success'], JsonResponse::HTTP_CREATED);
    }
}
