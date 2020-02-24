<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration;

use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Message\User\DisableUser;
use Buddy\Repman\MessageHandler\Organization\SynchronizePackageHandler;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Buddy\Repman\Service\PackageSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
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

    public function createOAuthUser(string $email = 'test@buddy.works'): void
    {
        $this->dispatchMessage(new CreateOAuthUser($email));
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

    public function createPackage(string $id): void
    {
        $this->dispatchMessage(
            new AddPackage(
                $id,
                $this->createOrganization('buddy', $this->createUser()),
                'https://github.com/buddy-works/repman',
                'vcs'
            )
        );
    }

    public function addPackage(string $orgId, string $url, string $type = 'vcs'): string
    {
        $this->dispatchMessage(
            new AddPackage(
                $id = Uuid::uuid4()->toString(),
                $orgId,
                $url,
                $type
            )
        );

        return $id;
    }

    public function disableUser(string $id): void
    {
        $this->dispatchMessage(new DisableUser($id));
    }

    public function syncPackageWithError(string $packageId, string $error): void
    {
        $this->container->get(PackageSynchronizer::class)->setError($error);
        $this->container->get(SynchronizePackageHandler::class)(new SynchronizePackage($packageId));
        $this->container->get(EntityManagerInterface::class)->flush();
    }

    public function syncPackageWithData(string $packageId, string $name, string $description, string $latestReleasedVersion, \DateTimeImmutable $latestReleaseDate): void
    {
        $this->container->get(PackageSynchronizer::class)->setData($name, $description, $latestReleasedVersion, $latestReleaseDate);
        $this->container->get(SynchronizePackageHandler::class)(new SynchronizePackage($packageId));
        $this->container->get(EntityManagerInterface::class)->flush();
    }

    private function dispatchMessage(object $message): void
    {
        $this->container->get(MessageBusInterface::class)->dispatch($message);
    }
}
