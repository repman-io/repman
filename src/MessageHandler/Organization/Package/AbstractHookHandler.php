<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Package;

use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\IntegrationRegister;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractHookHandler implements MessageHandlerInterface
{
    protected PackageRepository $packages;
    protected IntegrationRegister $integrations;
    protected UrlGeneratorInterface $router;
    protected ClientRegistry $oauth;

    public function __construct(PackageRepository $packages, IntegrationRegister $integrations, UrlGeneratorInterface $router, ClientRegistry $oauth)
    {
        $this->packages = $packages;
        $this->integrations = $integrations;
        $this->router = $router;
        $this->oauth = $oauth;
    }
}
