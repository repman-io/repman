<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Query\User\Model\Organization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class RepoController extends AbstractController
{
    /**
     * @Route("/packages.json", host="{organization}.repo.{domain}", name="repo_packages", methods={"GET"}, defaults={"domain":"%domain%"}, requirements={"domain"="%domain%"})
     */
    public function packages(Organization $organization): JsonResponse
    {
        $proxyUrl = $this->generateUrl('proxy_repo_url', [], RouterInterface::ABSOLUTE_URL);

        return new JsonResponse([
            'notify-batch' => 'https://packagist.org/downloads/',
            'providers-url' => $proxyUrl.'p/%package%$%hash%.json',
            'metadata-url' => $proxyUrl.'p2/%package%.json',
            'search' => 'https://packagist.org/search.json?q=%query%&type=%type%',
            'mirrors' => [
                [
                    'dist-url' => $proxyUrl.'dists/%package%/%version%/%reference%.%type%',
                    'preferred' => true,
                ],
            ],
            'providers-lazy-url' => $proxyUrl.'p/%package%',
        ]);
    }
}
