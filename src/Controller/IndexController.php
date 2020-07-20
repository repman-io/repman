<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Service\Telemetry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function index(): Response
    {
        $showTelemetryPrompt = !$this->telemetry->isInstanceIdPresent();

        return $this->render('index.html.twig', [
            'showTelemetryPrompt' => $showTelemetryPrompt,
            'telemetryDocsUrl' => $this->telemetry->docsUrl(),
        ]);
    }
}
