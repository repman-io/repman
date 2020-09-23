<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Message\Proxy\AddDownloads;
use Buddy\Repman\Message\Proxy\AddDownloads\Package;
use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\Metadata;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Buddy\Repman\Service\Symfony\ResponseCallback;
use Munus\Control\Option;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @Route(
     *     "/packages.json",
     *     host="repo.{domain}",
     *     name="packages",
     *     methods={"GET"},
     *     defaults={"domain"="%domain%"},
     *     requirements={"domain"="%domain%"}
     * )
     */
    public function packages(Request $request): JsonResponse
    {
        $metadata = $this->register->getByHost('packagist.org')->latestProvider();
        $response = (new JsonResponse([
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
            'provider-includes' => $metadata->isPresent() ? ['p/provider-latest$%hash%.json' => ['sha256' => $metadata->get()->hash()]] : [],
        ]))
            ->setPublic()
        ;

        $now = new \DateTime();
        $response->setLastModified(
            $metadata->isPresent() ?
            $now->setTimestamp($metadata->get()->timestamp()) :
            $now
        );

        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Route("/p/{package}${hash}.json",
     *     name="package_legacy_metadata",
     *     host="repo.{domain}",
     *     defaults={"domain"="%domain%"},
     *     requirements={"package"="%package_name_pattern%","domain"="%domain%"},
     *     methods={"GET"})
     */
    public function legacyMetadata(string $package, string $hash, Request $request): Response
    {
        /** @var Metadata $metadata */
        $metadata = $this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->legacyMetadata($package, $hash))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElseThrow(new NotFoundHttpException('Provider not found'));

        $response = (new StreamedResponse(ResponseCallback::fromStream($metadata->stream()), 200, [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'application/json',
            /* @phpstan-ignore-next-line */
            'Content-Length' => fstat($metadata->stream())['size'],
        ]))
            ->setPublic()
            ->setLastModified((new \DateTime())->setTimestamp($metadata->timestamp()))
        ;

        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Route("/p/{package}",
     *     name="package_legacy_metadata_lazy",
     *     host="repo.{domain}",
     *     defaults={"domain"="%domain%"},
     *     requirements={"package"="%package_name_pattern%","domain"="%domain%"},
     *     methods={"GET"})
     */
    public function legacyMetadataLazy(string $package, Request $request): Response
    {
        /** @var Metadata $metadata */
        $metadata = $this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->legacyMetadata($package))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElse(Metadata::fromString('{"packages": {}}'));

        $response = (new StreamedResponse(ResponseCallback::fromStream($metadata->stream()), 200, [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'application/json',
            /* @phpstan-ignore-next-line */
            'Content-Length' => fstat($metadata->stream())['size'],
        ]))
            ->setPublic()
            ->setLastModified((new \DateTime())->setTimestamp($metadata->timestamp()))
        ;

        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Route(
     *     "/p/provider-{version}${hash}.json",
     *     host="repo.{domain}",
     *     name="providers",
     *     methods={"GET"},
     *     defaults={"domain"="%domain%"}, requirements={"domain"="%domain%"}
     * )
     */
    public function providers(string $version, string $hash, Request $request): Response
    {
        /** @var Metadata $metadata */
        $metadata = $this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->providers($version, $hash))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElseThrow(new NotFoundHttpException('Provider not found'));

        $response = (new StreamedResponse(ResponseCallback::fromStream($metadata->stream()), 200, [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'application/json',
            /* @phpstan-ignore-next-line */
            'Content-Length' => fstat($metadata->stream())['size'],
        ]))
            ->setPublic()
            ->setLastModified((new \DateTime())->setTimestamp($metadata->timestamp()))
        ;

        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Route("/p2/{package}.json",
     *     name="package_metadata",
     *     host="repo.{domain}",
     *     defaults={"domain"="%domain%"},
     *     requirements={"package"="%package_name_pattern%","domain"="%domain%"},
     *     methods={"GET"})
     */
    public function metadata(string $package, Request $request): Response
    {
        /** @var Metadata $metadata */
        $metadata = $this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->metadata($package))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElseThrow(new NotFoundHttpException('Metadata not found'));

        $response = (new StreamedResponse(ResponseCallback::fromStream($metadata->stream()), 200, [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'application/json',
            /* @phpstan-ignore-next-line */
            'Content-Length' => fstat($metadata->stream())['size'],
        ]))
            ->setPublic()
            ->setLastModified((new \DateTime())->setTimestamp($metadata->timestamp()))
        ;

        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Route("/dists/{package}/{version}/{ref}.{type}",
     *     name="package_dist",
     *     host="repo.{domain}",
     *     defaults={"domain"="%domain%"},
     *     requirements={"package"="%package_name_pattern%","ref"="[a-f0-9]*?","type"="zip|tar","domain"="%domain%"},
     *     methods={"GET"})
     */
    public function distribution(string $package, string $version, string $ref, string $type, Request $request): Response
    {
        /** @var resource $stream */
        $stream = $this->register->all()
            ->map(fn (Proxy $proxy) => $proxy->distribution($package, $version, $ref, $type))
            ->find(fn (Option $option) => !$option->isEmpty())
            ->map(fn (Option $option) => $option->get())
            ->getOrElseThrow(new NotFoundHttpException('This distribution file can not be found or downloaded from origin url.'));

        $response = (new StreamedResponse(ResponseCallback::fromStream($stream), 200, [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'application/zip',
            /* @phpstan-ignore-next-line */
            'Content-Length' => fstat($stream)['size'],
        ]))
            ->setPublic()
            ->setEtag($ref)
        ;

        $response->isNotModified($request);

        return $response;
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
