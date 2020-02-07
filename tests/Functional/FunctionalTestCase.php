<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional;

use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Service\Organization\TokenGenerator;
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

    /**
     * @param array<mixed> $parameters
     */
    protected function urlTo(string $path, array $parameters = []): string
    {
        return $this->container()->get('router')->generate($path, $parameters);
    }

    protected function lastResponseBody(): string
    {
        return (string) $this->client->getResponse()->getContent();
    }

    protected function createAdmin(string $email, string $password): string
    {
        $this->container()->get(MessageBusInterface::class)->dispatch(
            new CreateUser(
                $id = Uuid::uuid4()->toString(),
                $email,
                $password,
                Uuid::uuid4()->toString(),
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

    public function createToken(string $orgId, string $value): void
    {
        $this->container()->get(TokenGenerator::class)->setNextToken($value);
        $this->container()->get(MessageBusInterface::class)->dispatch(
            new GenerateToken(
                $orgId,
                'token'
            )
        );
    }

    public function addPackage(string $orgId, string $url): string
    {
        $this->container()->get(MessageBusInterface::class)->dispatch(
            new AddPackage(
                $id = Uuid::uuid4()->toString(),
                $orgId,
                $url
            )
        );

        return $id;
    }

    protected function container(): ContainerInterface
    {
        return self::$kernel->getContainer()->get('test.service_container');
    }
}
