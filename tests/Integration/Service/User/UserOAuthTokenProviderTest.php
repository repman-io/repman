<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Service\User;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Service\User\UserOAuthTokenProvider;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * This is the only place a token is refreshed while a page waits to be rendered -
 * everything else that refreshes one does it from a message handler. What that costs
 * when another process is already refreshing the same token is therefore a property of
 * how this service is wired, and is what the test below pins down.
 *
 * The fixture is committed by hand because the store works outside the connection the
 * test transaction wraps.
 */
final class UserOAuthTokenProviderTest extends IntegrationTestCase
{
    /**
     * What the request path may spend waiting for a lock it is not going to get. Well
     * above the wait oauth.token_store.request is configured for, so a busy machine
     * cannot fail this, and well below the default one, which is what a regression
     * would leave here.
     */
    private const ACCEPTABLE_WAIT_MS = 8000;

    private Connection $connection;

    private UuidInterface $tokenId;

    private UuidInterface $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DriverManager::getConnection(['url' => self::databaseUrl()]);
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

    public function testStoredTokenIsReturnedWithoutRefreshingWhileItIsStillValid(): void
    {
        $this->connection->update(
            'user_oauth_token',
            ['expires_at' => (new \DateTimeImmutable())->modify('+1 hour')->format('Y-m-d H:i:s')],
            ['id' => $this->tokenId->toString()]
        );

        self::assertSame(
            'expired-access-token',
            $this->provider()->findAccessToken($this->userId->toString(), OAuthToken::TYPE_BITBUCKET)
        );
    }

    public function testMissingIntegrationIsReportedWithoutTouchingTheStore(): void
    {
        self::assertNull($this->provider()->findAccessToken($this->userId->toString(), OAuthToken::TYPE_GITHUB));
    }

    /**
     * A worker can sit out the default wait; a page cannot. Giving up and reporting it
     * leaves the user a request that failed and a retry that finds the token the other
     * process committed, instead of a page that hangs long enough to look broken.
     */
    public function testRefreshBlockedByAnotherProcessGivesUpWithoutHoldingTheRequest(): void
    {
        // stand in for the worker that is already refreshing this token, and holds the
        // lock for longer than the request path is willing to wait
        $this->connection->beginTransaction();
        $this->connection->fetchAssociative(
            'SELECT id FROM user_oauth_token WHERE id = :id FOR UPDATE',
            ['id' => $this->tokenId->toString()]
        );

        $startedAt = microtime(true);

        try {
            $this->provider()->findAccessToken($this->userId->toString(), OAuthToken::TYPE_BITBUCKET);
            self::fail('Expected the refresh to be locked out by the concurrent one');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('lock timeout', $exception->getMessage());
        } finally {
            $this->connection->rollBack();
        }

        self::assertLessThan(self::ACCEPTABLE_WAIT_MS, (microtime(true) - $startedAt) * 1000);
    }

    private function provider(): UserOAuthTokenProvider
    {
        return $this->container()->get(UserOAuthTokenProvider::class);
    }
}
