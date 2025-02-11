<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Service\Organization\WebhookRequests;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

final class WebhookController extends AbstractController
{
    public function __construct(private readonly WebhookRequests $webhookRequests, private readonly MessageBusInterface $messageBus)
    {
    }

    /**
     * @Route("/hook/{package}", name="package_webhook", methods={"POST"})
     */
    public function package(Package $package, Request $request): Response
    {
        $this->messageBus->dispatch(new SynchronizePackage($package->id()));
        $this->webhookRequests->add($package->id(), new DateTimeImmutable(), $request->getClientIp(), $request->headers->get('User-Agent'));

        return new JsonResponse(null, Response::HTTP_ACCEPTED);
    }
}
