<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

use Buddy\Repman\Form\Type\Admin\ConfigType;
use Buddy\Repman\Message\Admin\ChangeConfig;
use Buddy\Repman\Service\Config;
use Buddy\Repman\Service\Telemetry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

final class ConfigController extends AbstractController
{
    private Config $config;
    private Telemetry $telemetry;
    private MessageBusInterface $messageBus;

    public function __construct(
        Config $config,
        Telemetry $telemetry,
        MessageBusInterface $messageBus
    ) {
        $this->config = $config;
        $this->telemetry = $telemetry;
        $this->messageBus = $messageBus;
    }

    /**
     * @Route("/admin/config", name="admin_config", methods={"GET","POST"})
     */
    public function edit(Request $request): Response
    {
        $form = $this->createForm(ConfigType::class, $this->config->getAll());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->messageBus->dispatch(new ChangeConfig($form->getData()));
            $this->addFlash('success', 'Configuration has been successfully changed');

            return $this->redirectToRoute('admin_config');
        }

        return $this->render('admin/config/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/config/telemetry", name="admin_config_toggle_telemetry", methods={"POST","DELETE"})
     */
    public function toggleTelemetry(Request $request): Response
    {
        $this->telemetry->generateInstanceId();
        $this->messageBus->dispatch(new ChangeConfig([
            Config::TELEMETRY => $request->isMethod(Request::METHOD_POST)
                ? Config::TELEMETRY_ENABLED
                : Config::TELEMETRY_DISABLED,
        ]));

        return $this->redirectToRoute('index');
    }
}
