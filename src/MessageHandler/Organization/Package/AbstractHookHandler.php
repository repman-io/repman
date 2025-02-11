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
    public function __construct(protected PackageRepository $packages, protected IntegrationRegister $integrations, protected UrlGeneratorInterface $router, protected UserOAuthTokenRefresher $tokenRefresher)
    {
    }
}
