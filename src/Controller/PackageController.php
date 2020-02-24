<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Query\User\Model\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PackageController extends AbstractController
{
    /**
     * @Route("/hook/{package}", name="package_webhook", methods={"POST"})
     */
    public function webhook(Package $package): Response
    {
        $this->dispatchMessage(new SynchronizePackage($package->id()));

        return new JsonResponse(null, Response::HTTP_ACCEPTED);
    }
}
