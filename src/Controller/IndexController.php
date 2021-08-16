<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Service\Telemetry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class IndexController extends AbstractController
{
    private Telemetry $telemetry;

    public function __construct(Telemetry $telemetry)
    {
        $this->telemetry = $telemetry;
    }

    /**
     * @Route(path="/", name="index", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        if ($request->getSession()->has('organization-token')) {
            return $this->redirectToRoute('organization_accept_invitation', ['token' => $request->getSession()->remove('organization-token')]);
        }

        return $this->render('index.html.twig', [
            'showTelemetryPrompt' => !$this->telemetry->isInstanceIdPresent(),
            'telemetryDocsUrl' => $this->telemetry->docsUrl(),
        ]);
    }
}
