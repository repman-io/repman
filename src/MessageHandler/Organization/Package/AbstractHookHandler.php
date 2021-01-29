<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Package;

use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\IntegrationRegister;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractHookHandler implements MessageHandlerInterface
{
    protected PackageRepository $packages;
    protected IntegrationRegister $integrations;
    protected UrlGeneratorInterface $router;
    protected UserOAuthTokenRefresher $tokenRefresher;

    public function __construct(PackageRepository $packages, IntegrationRegister $integrations, UrlGeneratorInterface $router, UserOAuthTokenRefresher $tokenRefresher)
    {
        $this->packages = $packages;
        $this->integrations = $integrations;
        $this->router = $router;
        $this->tokenRefresher = $tokenRefresher;
    }
}
