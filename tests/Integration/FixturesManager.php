<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration;

use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Entity\Organization\Package\ScanResult;
use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Message\Admin\ChangeConfig;
use Buddy\Repman\Message\Organization\AddDownload;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\Organization\Member\AcceptInvitation;
use Buddy\Repman\Message\Organization\Member\InviteUser;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Message\Proxy\AddDownloads;
use Buddy\Repman\Message\User\AddOAuthToken;
use Buddy\Repman\Message\User\ConfirmEmail;
use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Message\User\DisableUser;
use Buddy\Repman\Message\User\GenerateApiToken;
use Buddy\Repman\MessageHandler\Proxy\AddDownloadsHandler;
use Buddy\Repman\Query\User\Model\Package\Link;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Repository\ScanResultRepository;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Buddy\Repman\Service\PackageSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Munus\Collection\Stream;
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

    public function createAdmin(string $email, string $password, ?string $confirmToken = null): string
    {
        return $this->createUser($email, $password, ['ROLE_ADMIN'], $confirmToken);
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

    public function createToken(string $orgId, string $value, string $name = 'token'): void
    {
        $this->container->get(TokenGenerator::class)->setNextToken($value);
        $this->dispatchMessage(
            new GenerateToken(
                $orgId,
                $name
            )
        );
    }

    public function createApiToken(string $userId, string $value = 'api-token', string $name = 'token'): void
    {
        $this->container->get(TokenGenerator::class)->setNextToken($value);
        $this->dispatchMessage(
            new GenerateApiToken($userId, $name)
        );
    }

    public function inviteUser(string $orgId, string $email, string $token, string $role = Member::ROLE_MEMBER): void
    {
        $this->dispatchMessage(new InviteUser($email, $role, $orgId, $token));
    }

    public function addAcceptedMember(string $orgId, string $email, string $role = Member::ROLE_MEMBER): string
    {
        $token = Uuid::uuid4()->toString();
        $this->dispatchMessage(new InviteUser($email, $role, $orgId, $token));
        $this->dispatchMessage(new AcceptInvitation($token, $userId = $this->createUser($email)));

        return $userId;
    }

    public function createPackage(string $id, string $organization = 'buddy', string $organizationId = null): void
    {
        $organization = $organizationId !== null ? $organizationId : $this->createOrganization($organization, $this->createUser());
        $this->dispatchMessage(
            new AddPackage(
                $id,
                $organization,
                'https://github.com/buddy-works/repman',
                'vcs'
            )
        );
    }

    /**
     * @param mixed[] $metadata
     */
    public function addPackage(string $orgId, string $url, string $type = 'vcs', array $metadata = []): string
    {
        $this->dispatchMessage(
            new AddPackage(
                $id = Uuid::uuid4()->toString(),
                $orgId,
                $url,
                $type,
                $metadata,
            )
        );

        return $id;
    }

    public function setWebhookCreated(string $packageId): void
    {
        $package = $this->container->get(PackageRepository::class)->getById(Uuid::fromString($packageId));
        $package->webhookWasCreated();
        $this->container->get('doctrine.orm.entity_manager')->flush($package);
    }

    public function setWebhookError(string $packageId, string $error): void
    {
        $package = $this->container->get(PackageRepository::class)->getById(Uuid::fromString($packageId));
        $package->webhookWasNotCreated($error);
        $this->container->get('doctrine.orm.entity_manager')->flush($package);
    }

    public function addPackageDownload(int $count, string $packageId, string $version = '1.0.0', ?\DateTimeImmutable $date = null): void
    {
        Stream::range(1, $count)->forEach(function (int $index) use ($packageId, $version, $date): void {
            $this->dispatchMessage(new AddDownload(
                $packageId,
                $version,
                $date ?? new \DateTimeImmutable(),
                '192.168.0.1',
                'Composer 19.10'
            ));
        });
    }

    /**
     * @param AddDownloads\Package[] $packages
     */
    public function addProxyPackageDownload(array $packages, \DateTimeImmutable $date): void
    {
        $this->container->get(AddDownloadsHandler::class)->__invoke(new AddDownloads(
            $packages,
            $date,
            '127.0.0.1',
            'Repman Fixtures'
        ));
    }

    public function disableUser(string $id): void
    {
        $this->dispatchMessage(new DisableUser($id));
    }

    public function syncPackageWithError(string $packageId, string $error): void
    {
        $this->container->get(PackageSynchronizer::class)->setError($error);
        $this->dispatchMessage(new SynchronizePackage($packageId));
        $this->container->get(EntityManagerInterface::class)->flush();
    }

    /**
     * @param Version[] $versions
     * @param Link[]    $links
     */
    public function syncPackageWithData(string $packageId, string $name, string $description, string $latestReleasedVersion, \DateTimeImmutable $latestReleaseDate, array $versions = [], array $links = [], ?string $readme = null, ?string $replacementPackage = null): void
    {
        $this->container->get(PackageSynchronizer::class)->setData($name, $description, $latestReleasedVersion, $latestReleaseDate, $versions, $links, $readme, $replacementPackage);
        $this->dispatchMessage(new SynchronizePackage($packageId));
        $this->container->get(EntityManagerInterface::class)->flush();
    }

    public function createOauthToken(
        string $userId,
        string $type,
        string $accessToken = 'secret',
        ?string $refreshToken = null,
        ?\DateTimeImmutable $expiresAt = null
    ): string {
        $this->dispatchMessage(
            new AddOAuthToken(
                $id = Uuid::uuid4()->toString(),
                $userId,
                $type,
                $accessToken,
                $refreshToken,
                $expiresAt
            )
        );

        return $id;
    }

    public function prepareRepoFiles(): void
    {
        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->mirror(
            __DIR__.'/../Resources/fixtures/buddy/dist/buddy-works/repman',
            __DIR__.'/../Resources/buddy/dist/buddy-works/repman',
            null,
            ['delete' => true]
        );
    }

    /**
     * @param mixed[] $content
     */
    public function addScanResult(string $packageId, string $status, array $content = ['composer.lock' => []]): void
    {
        $date = new \DateTimeImmutable();
        $package = $this->container
            ->get(PackageRepository::class)
            ->getById(Uuid::fromString($packageId));
        $package->setScanResult($status, $date, $content);
        $this->container->get(ScanResultRepository::class)->add(
            new ScanResult(
                Uuid::uuid4(),
                $package,
                $date,
                $status,
                $content
            )
        );
        $this->container->get(EntityManagerInterface::class)->flush();
    }

    public function confirmUserEmail(string $token): void
    {
        $this->dispatchMessage(new ConfirmEmail($token));
        $this->container->get(EntityManagerInterface::class)->flush();
    }

    public function enableAnonymousUserAccess(string $organizationId): void
    {
        $organization = $this->container->get(OrganizationRepository::class)->getById(Uuid::fromString($organizationId));
        $organization->changeAnonymousAccess(true);
        $this->container->get('doctrine.orm.entity_manager')->flush($organization);
    }

    public function changeConfig(string $key, string $value): void
    {
        $this->dispatchMessage(new ChangeConfig([$key => $value]));
    }

    private function dispatchMessage(object $message): void
    {
        $this->container->get(MessageBusInterface::class)->dispatch($message);
    }
}
