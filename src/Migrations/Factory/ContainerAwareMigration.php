<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * For the few migrations that move files rather than schema; Symfony's ContainerAwareInterface
 * is deprecated since 6.4.
 */
interface ContainerAwareMigration
{
    public function setContainer(ContainerInterface $container): void;
}
