<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Service\Telemetry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

final class IndexController extends AbstractController
{
    private SessionInterface $session;
    private Telemetry $telemetry;

    public function __construct(SessionInterface $session, Telemetry $telemetry)
    {
        $this->session = $session;
        $this->telemetry = $telemetry;
    }

    /**
     * @Route(path="/", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        if ($this->session->has('organization-token')) {
            return $this->redirectToRoute('organization_accept_invitation', ['token' => $this->session->remove('organization-token')]);
        }

        return $this->render('index.html.twig', [
            'showTelemetryPrompt' => !$this->telemetry->isInstanceIdPresent(),
            'telemetryDocsUrl' => $this->telemetry->docsUrl(),
        ]);
    }
}
