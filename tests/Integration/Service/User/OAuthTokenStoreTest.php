<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Service\User;

use Buddy\Repman\Service\User\OAuthTokenStore;
use Buddy\Repman\Service\User\OAuthTokenStore\StoredToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\AccessToken;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\ConnectionException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * The store deliberately works outside the connection the test transaction wraps, so
 * the fixture here is committed by hand and cleaned up afterwards instead of being
 * rolled back with the rest of the test.
 */
final class OAuthTokenStoreTest extends IntegrationTestCase
{
    private const EXPIRES_AT = '2026-01-01 10:00:00';

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
            'type' => 'bitbucket',
            'access_token' => 'stored-access-token',
            'refresh_token' => 'stored-refresh-token',
            'expires_at' => self::EXPIRES_AT,
        ]);
    }

    protected function tearDown(): void
    {
        $this->connection->delete('user_oauth_token', ['id' => $this->tokenId->toString()]);
        $this->connection->delete('"user"', ['id' => $this->userId->toString()]);
        $this->connection->close();

        parent::tearDown();
    }

    public function testCallbackReceivesTheTokenAsCurrentlyStored(): void
    {
        $seen = null;

        $this->store()->refreshExclusively($this->tokenId, function (StoredToken $stored) use (&$seen): ?AccessToken {
            $seen = $stored;

            return null;
        });

        self::assertEquals(
            new StoredToken('stored-access-token', 'stored-refresh-token', new \DateTimeImmutable(self::EXPIRES_AT)),
            $seen
        );
    }

    public function testRefreshedTokenIsCommittedImmediately(): void
    {
        $expiresAt = new \DateTimeImmutable('2026-06-01 12:00:00');

        $returned = $this->store()->refreshExclusively(
            $this->tokenId,
            static fn (StoredToken $stored): AccessToken => new AccessToken('rotated-access-token', $expiresAt, 'rotated-refresh-token')
        );

        self::assertEquals(new AccessToken('rotated-access-token', $expiresAt, 'rotated-refresh-token'), $returned);
        // read on a separate connection: a write still pending in a transaction would not show up
        self::assertSame(
            ['access_token' => 'rotated-access-token', 'refresh_token' => 'rotated-refresh-token', 'expires_at' => '2026-06-01 12:00:00'],
            $this->storedRow()
        );
    }

    public function testRefreshTokenIsKeptWhenTheProviderDoesNotRotateIt(): void
    {
        $this->store()->refreshExclusively(
            $this->tokenId,
            static fn (StoredToken $stored): AccessToken => new AccessToken('new-access-token', new \DateTimeImmutable('2026-06-01 12:00:00'))
        );

        self::assertSame(
            ['access_token' => 'new-access-token', 'refresh_token' => 'stored-refresh-token', 'expires_at' => '2026-06-01 12:00:00'],
            $this->storedRow()
        );
    }

    /**
     * Recording "no expiry" - which is what a response without expires_in literally
     * says - would leave a token that is never considered expired, so never refreshed
     * again, with no way back once it really expires. A short assumed lifetime keeps
     * the refreshes coming until a response states a real expiry.
     */
    public function testTokenRefreshedWithoutAnExpiryIsGivenAShortAssumedOne(): void
    {
        $returned = $this->store()->refreshExclusively(
            $this->tokenId,
            static fn (StoredToken $stored): AccessToken => new AccessToken('new-access-token')
        );

        $row = $this->storedRow();
        self::assertNotNull($row['expires_at']);
        $storedExpiry = (new \DateTimeImmutable($row['expires_at']))->getTimestamp();

        self::assertGreaterThan(time(), $storedExpiry);
        self::assertLessThanOrEqual(time() + 600, $storedExpiry);
        // and the caller is handed the expiry that was written, not the absent one it gave
        self::assertEquals($storedExpiry, $returned->expiresAt() !== null ? $returned->expiresAt()->getTimestamp() : null);
    }

    public function testStoredTokenIsLeftAloneWhenThereIsNothingToRefresh(): void
    {
        $returned = $this->store()->refreshExclusively($this->tokenId, static fn (StoredToken $stored): ?AccessToken => null);

        self::assertEquals(
            new AccessToken('stored-access-token', new \DateTimeImmutable(self::EXPIRES_AT), 'stored-refresh-token'),
            $returned
        );
        self::assertSame(
            ['access_token' => 'stored-access-token', 'refresh_token' => 'stored-refresh-token', 'expires_at' => self::EXPIRES_AT],
            $this->storedRow()
        );
    }

    public function testFailedRefreshLeavesTheStoredTokenUntouched(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid_grant');

        try {
            $this->store()->refreshExclusively($this->tokenId, static function (StoredToken $stored): AccessToken {
                throw new \RuntimeException('invalid_grant');
            });
        } finally {
            self::assertSame(
                ['access_token' => 'stored-access-token', 'refresh_token' => 'stored-refresh-token', 'expires_at' => self::EXPIRES_AT],
                $this->storedRow()
            );
        }
    }

    public function testConcurrentRefreshIsLockedOutAndRetriedBeforeGivingUp(): void
    {
        $lockTimeoutMs = 200;

        // stand in for a worker that keeps refreshing this token for longer than any
        // retry will wait, so every attempt runs out of patience
        $this->connection->beginTransaction();
        $this->connection->fetchAssociative('SELECT id FROM user_oauth_token WHERE id = :id FOR UPDATE', ['id' => $this->tokenId->toString()]);

        $startedAt = microtime(true);

        try {
            (new OAuthTokenStore(self::databaseUrl(), $lockTimeoutMs))->refreshExclusively(
                $this->tokenId,
                static fn (StoredToken $stored): AccessToken => new AccessToken('must-not-be-stored')
            );
            self::fail('Expected the refresh to be locked out by the concurrent one');
        } catch (DbalException $exception) {
            self::assertStringContainsString('lock timeout', $exception->getMessage());
        } finally {
            $this->connection->rollBack();
        }

        $elapsedMs = (microtime(true) - $startedAt) * 1000;

        // Two lock waits, so the timeout was retried rather than failing the caller on
        // the first one. Asserted as a lower bound only: a loaded machine makes this
        // longer, never shorter, so there is nothing here to go flaky.
        self::assertGreaterThan($lockTimeoutMs * 1.75, $elapsedMs);

        // and the locked out worker never spent the refresh token behind our back
        self::assertSame(
            ['access_token' => 'stored-access-token', 'refresh_token' => 'stored-refresh-token', 'expires_at' => self::EXPIRES_AT],
            $this->storedRow()
        );
    }

    /**
     * The refresh has already happened at the provider by the time anything is written,
     * so a database failure here is the error an operator has to see: with a rotating
     * refresh token the stored one is now dead. Attempting a rollback on the broken
     * connection used to bury it under "There is no active transaction".
     */
    public function testDatabaseFailureIsReportedAsItself(): void
    {
        $this->expectException(DbalException::class);
        $this->expectExceptionMessageMatches('/terminating connection|server closed the connection|connection|EOF/i');
        $this->expectExceptionMessageMatches('/^(?!.*no active transaction).*$/is');

        $this->store()->refreshExclusively($this->tokenId, function (StoredToken $stored): AccessToken {
            // the token row is locked by now, so the connection holding that lock is the
            // store's - killing it makes the write that follows fail
            $this->terminateLockHolder();

            return new AccessToken('rotated-access-token', null, 'rotated-refresh-token');
        });
    }

    /**
     * A server out of connection slots is exactly what a fleet of workers whose tokens
     * expire together can produce, and it is transient - failing a whole package
     * synchronization over it without so much as a second try would be a waste.
     */
    public function testRefusedConnectionIsRetriedBeforeGivingUp(): void
    {
        $retryDelayMs = 50;
        // A socket that does not exist, which libpq reports as the same SQLSTATE[08006]
        // an exhausted server does - and reports the moment it is asked, because no
        // network is involved. A closed TCP port would be the more obvious stand-in,
        // but only a host that answers with a refusal makes it a fast one: where such
        // packets are dropped instead, every attempt would sit in the OS connect
        // timeout and this test would look like it had hung rather than failed. The
        // connect_timeout that would bound that never reaches libpq - DBAL's PgSQL
        // driver builds its DSN from the host, port, dbname and ssl parameters only.
        $store = new OAuthTokenStore('postgresql://main:main@%2Fnonexistent-socket-dir/main?serverVersion=17&charset=utf8', 200, $retryDelayMs);

        $startedAt = microtime(true);

        try {
            $store->refreshExclusively($this->tokenId, static fn (StoredToken $stored): ?AccessToken => null);
            self::fail('Expected the refresh to fail with no connection available');
        } catch (ConnectionException $exception) {
            self::assertStringContainsString('SQLSTATE[08006]', $exception->getMessage());
        }

        $elapsedMs = (microtime(true) - $startedAt) * 1000;

        // Three attempts, so two pauses. A lower bound only: a loaded machine makes this
        // longer, never shorter, so there is nothing here to go flaky.
        self::assertGreaterThan($retryDelayMs * 1.75, $elapsedMs);
    }

    public function testRemovedTokenIsReported(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no longer exists');

        $this->store()->refreshExclusively(
            Uuid::uuid4(),
            static fn (StoredToken $stored): ?AccessToken => null
        );
    }

    /**
     * SELECT ... FOR UPDATE takes a RowShareLock on the table, and nothing else in the
     * test does, so this is the store's own connection.
     */
    private function terminateLockHolder(): void
    {
        $pid = $this->connection->fetchOne(
            'SELECT l.pid FROM pg_locks l JOIN pg_class c ON c.oid = l.relation
             WHERE c.relname = :table AND l.mode = :mode AND l.granted AND l.pid <> pg_backend_pid()',
            ['table' => 'user_oauth_token', 'mode' => 'RowShareLock']
        );

        self::assertNotFalse($pid, 'Expected the store to be holding the row lock');

        $this->connection->executeStatement('SELECT pg_terminate_backend(:pid)', ['pid' => $pid]);
    }

    private function store(): OAuthTokenStore
    {
        return $this->container()->get(OAuthTokenStore::class);
    }

    /**
     * @return array{access_token: string, refresh_token: string|null, expires_at: string|null}
     */
    private function storedRow(): array
    {
        /** @var array{access_token: string, refresh_token: string|null, expires_at: string|null} $row */
        $row = $this->connection->fetchAssociative(
            'SELECT access_token, refresh_token, expires_at FROM user_oauth_token WHERE id = :id',
            ['id' => $this->tokenId->toString()]
        );

        return $row;
    }
}
