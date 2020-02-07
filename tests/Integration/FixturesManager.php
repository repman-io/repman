<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration;

use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer;
use Symfony\Component\Messenger\MessageBusInterface;

final class FixturesManager
{
    private TestContainer $container;

    public function __construct(TestContainer $container)
    {
        $this->container = $container;
    }

    /**
     * @param array<string> $roles
     */
    public function createUser(string $email = 'test@buddy.works', string $password = 'secret', array $roles = ['ROLE_USER'], ?string $confirmToken = null): string
    {
        $this->dispatchMessage(new CreateUser(
            $id = Uuid::uuid4()->toString(),
            $email,
            $password,
            $confirmToken ?? Uuid::uuid4()->toString(),
            $roles
        ));

        return $id;
    }

    public function createAdmin(string $email, string $password): string
    {
        return $this->createUser($email, $password, ['ROLE_ADMIN']);
    }

    public function createOrganization(string $name, string $ownerId): string
    {
        $this->dispatchMessage(
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
        $this->container->get(TokenGenerator::class)->setNextToken($value);
        $this->dispatchMessage(
            new GenerateToken(
                $orgId,
                'token'
            )
        );
    }

    public function addPackage(string $orgId, string $url): string
    {
        $this->dispatchMessage(
            new AddPackage(
                $id = Uuid::uuid4()->toString(),
                $orgId,
                $url
            )
        );

        return $id;
    }

    private function dispatchMessage(object $message): void
    {
        $this->container->get(MessageBusInterface::class)->dispatch($message);
    }
}
