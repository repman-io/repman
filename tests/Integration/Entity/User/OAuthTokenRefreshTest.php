<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Entity\User;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Service\User\OAuthTokenStore;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken as LeagueAccessToken;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Guards the rule that the store is the only writer of the token columns: refreshing a
 * managed entity must not leave it dirty, or the next flush would push stale values out
 * on the default connection, unlocked, over whatever another process committed since.
 *
 * The fixture is committed by hand because the store works outside the connection the
 * test transaction wraps.
 */
final class OAuthTokenRefreshTest extends IntegrationTestCase
{
    private Connection $connection;

    private UuidInterface $tokenId;

    private UuidInterface $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DriverManager::getConnection(['url' => self::databaseUrl()]);
        // Should a regression ever leave the entity dirty, the resulting flush holds a
        // row lock inside the test transaction and the cleanup below would wait on it
        // forever. Fail the test instead of hanging the suite.
        $this->connection->executeStatement('SET lock_timeout = 5000');

        $this->tokenId = Uuid::uuid4();
        $this->userId = Uuid::uuid4();

        $this->connection->insert('"user"', [
            'id' => $this->userId->toString(),
            'email' => $this->userId->toString().'@buddy.works',
            'roles' => '[]',
            'password' => 'password',
            'created_at' => '2026-01-01 09:00:00',
            'email_confirm_token' => $this->userId->toString(),
            'status' => 'enabled',
            'email_scan_result' => 'false',
            'timezone' => 'UTC',
        ]);
        $this->connection->insert('user_oauth_token', [
            'id' => $this->tokenId->toString(),
            'user_id' => $this->userId->toString(),
            'created_at' => '2026-01-01 09:00:00',
            'type' => OAuthToken::TYPE_BITBUCKET,
            'access_token' => 'expired-access-token',
            'refresh_token' => 'stored-refresh-token',
            'expires_at' => '2026-01-01 10:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        $this->connection->delete('user_oauth_token', ['id' => $this->tokenId->toString()]);
        $this->connection->delete('"user"', ['id' => $this->userId->toString()]);
        $this->connection->close();

        parent::tearDown();
    }

    public function testRefreshLeavesNothingPendingOnTheManagedEntity(): void
    {
        $entityManager = $this->entityManager();
        $token = $entityManager->find(OAuthToken::class, $this->tokenId);
        self::assertInstanceOf(OAuthToken::class, $token);

        self::assertSame(
            'refreshed-access-token',
            $token->accessToken($this->refresherReturning('refreshed-access-token', 'refreshed-refresh-token'))
        );

        // A pending change here would be flushed on the default connection, unlocked and
        // without re-reading, overwriting whatever another process rotated in the
        // meantime. Asserting on the change set rather than on the row afterwards is
        // deliberate: the flush would happen inside the test transaction, so no other
        // connection could observe it, and the row lock it takes would then deadlock
        // against this test's own cleanup.
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();

        self::assertSame([], $unitOfWork->getEntityChangeSet($token));
    }

    public function testStoreCommitsTheRefreshedTokenOnItsOwn(): void
    {
        $token = $this->entityManager()->find(OAuthToken::class, $this->tokenId);
        self::assertInstanceOf(OAuthToken::class, $token);

        $token->accessToken($this->refresherReturning('refreshed-access-token', 'refreshed-refresh-token'));

        // visible from another connection without any flush, so the entity never has to
        // carry the write - which is what keeps it clean
        self::assertSame(
            ['access_token' => 'refreshed-access-token', 'refresh_token' => 'refreshed-refresh-token'],
            $this->storedRow()
        );
    }

    private function refresherReturning(string $accessToken, string $refreshToken): UserOAuthTokenRefresher
    {
        $provider = $this->createMock(AbstractProvider::class);
        $provider->method('getAccessToken')->willReturn(new LeagueAccessToken([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 7200,
        ]));

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->method('getOAuth2Provider')->willReturn($provider);
        $registry = $this->createMock(ClientRegistry::class);
        $registry->method('getClient')->willReturn($client);

        return new UserOAuthTokenRefresher($registry, $this->container()->get(OAuthTokenStore::class));
    }

    private function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container()->get('doctrine.orm.entity_manager');

        return $entityManager;
    }

    /**
     * @return array{access_token: string, refresh_token: string|null}
     */
    private function storedRow(): array
    {
        /** @var array{access_token: string, refresh_token: string|null} $row */
        $row = $this->connection->fetchAssociative(
            'SELECT access_token, refresh_token FROM user_oauth_token WHERE id = :id',
            ['id' => $this->tokenId->toString()]
        );

        return $row;
    }
}
