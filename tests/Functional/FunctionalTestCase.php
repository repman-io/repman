<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional;

use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Message\User\CreateUser;
use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class FunctionalTestCase extends WebTestCase
{
    use PHPMatcherAssertions;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function createAdmin(string $email, string $password): string
    {
        $this->container()->get(MessageBusInterface::class)->dispatch(
            new CreateUser(
                $id = Uuid::uuid4()->toString(),
                $email,
                $password,
                ['ROLE_ADMIN']
            )
        );

        return $id;
    }

    protected function createAndLoginAdmin(string $email = 'test@buddy.works', string $password = 'password'): string
    {
        $id = $this->createAdmin($email, $password);

        if (static::$booted) {
            $this->ensureKernelShutdown();
        }
        $this->client = static::createClient([], [
            'PHP_AUTH_USER' => $email,
            'PHP_AUTH_PW' => $password,
        ]);

        return $id;
    }

    protected function createOrganization(string $name, string $ownerId): string
    {
        $this->container()->get(MessageBusInterface::class)->dispatch(
            new CreateOrganization(
                $id = Uuid::uuid4()->toString(),
                $ownerId,
                $name
            )
        );

        return $id;
    }

    protected function container(): ContainerInterface
    {
        return self::$kernel->getContainer()->get('test.service_container');
    }
}
