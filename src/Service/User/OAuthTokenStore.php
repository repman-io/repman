<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User;

use Buddy\Repman\Service\User\OAuthTokenStore\StoredToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\AccessToken;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Ramsey\Uuid\UuidInterface;

/**
 * Refreshes an OAuth token on a dedicated, short-lived connection.
 *
 * Providers with rotating refresh tokens (Bitbucket since 2026-05-04) invalidate the
 * previous refresh token on every use, which makes a refresh irreversible: losing the
 * rotated token locks the user out until they authorize the app again. That rules out
 * doing the write on the main connection, where the doctrine_transaction middleware
 * would tie it to the fate of the whole message - a rollback, or a worker killed
 * mid-handling, would discard a refresh the provider has already performed.
 *
 * The own connection also lets the row lock be held just for the token request instead
 * of for the entire, potentially minutes-long, package synchronization.
 */
class OAuthTokenStore
{
    /**
     * How long to wait for whoever holds the lock. Only one thing ever holds it - a
     * process in the middle of its own refresh - so this needs to cover a single slow
     * token request, and exists to bound the wait rather than tie a worker up
     * indefinitely. Nothing else writes these columns on the main connection: the only
     * ORM writes to the table are inserting and deleting whole tokens, neither of which
     * can be in flight while the same token is being refreshed.
     *
     * Note this bounds one attempt, so the wait before giving up is this times
     * MAX_ATTEMPTS. That is a wait for a background job to sit out; the request path
     * is wired with a much shorter one, since nothing there can hold a page open for
     * a refresh another process is already doing - see oauth.token_store.request.
     */
    public const DEFAULT_LOCK_TIMEOUT_MS = 10000;

    /**
     * PostgreSQL lock_not_available, which is how a lock_timeout surfaces. DBAL has no
     * dedicated exception class for it, so it arrives as a plain driver exception.
     */
    private const LOCK_NOT_AVAILABLE = '55P03';

    /**
     * Timing out means someone else was refreshing for the whole wait, and by now has
     * almost certainly finished. A second pass then finds the token already fresh and
     * returns without touching the provider, instead of failing a whole package
     * synchronization over a race it lost.
     */
    private const MAX_ATTEMPTS = 2;

    /**
     * A refusal to connect at all - the server out of slots, or a pooler with nothing
     * free - fails instantly, so unlike a lock timeout it has spent no time of its own
     * and retrying immediately would just meet the same full server. The refreshes
     * causing the crowding each hold their connection for as long as their token
     * request takes, so the wait has to be in that order of magnitude to help.
     */
    public const DEFAULT_CONNECT_RETRY_DELAY_MS = 1000;

    private const MAX_CONNECT_ATTEMPTS = 3;

    /**
     * What to record for a refreshed token whose expiry the provider did not state.
     * Storing "no expiry" instead - which is what the absent value literally says -
     * would be the worst reading here: the token would never be considered expired
     * again, so it would never be refreshed again, and once it really did expire every
     * call would fail with no way back short of the user re-authorizing. Assuming a
     * short life keeps the refreshes coming, and the next response can put the real
     * expiry back.
     *
     * Applied here rather than at the caller because this is the only place these
     * columns are written, so it is the only place the assumption cannot be bypassed.
     * A token that has never been refreshed can still be stored with no expiry - a
     * never-expiring one like GitHub's legitimately has none - but that row is written
     * by the ORM when the user authorizes the app, and read here without being touched.
     */
    private const ASSUMED_LIFETIME_SECONDS = 600;

    private string $databaseUrl;
    private int $lockTimeoutMs;
    private int $connectRetryDelayMs;

    public function __construct(
        string $databaseUrl,
        int $lockTimeoutMs = self::DEFAULT_LOCK_TIMEOUT_MS,
        int $connectRetryDelayMs = self::DEFAULT_CONNECT_RETRY_DELAY_MS
    ) {
        $this->databaseUrl = $databaseUrl;
        $this->lockTimeoutMs = $lockTimeoutMs;
        $this->connectRetryDelayMs = $connectRetryDelayMs;
    }

    /**
     * Runs $refresh while holding a row lock on the token, so concurrent workers
     * refresh it one at a time instead of racing for the same refresh token. The
     * callback gets the token as currently stored and returns the refreshed one, or
     * null to keep what is already there. A returned token is committed immediately.
     *
     * @param callable(StoredToken): ?AccessToken $refresh
     */
    public function refreshExclusively(UuidInterface $id, callable $refresh): AccessToken
    {
        $attempt = 0;

        while (true) {
            ++$attempt;

            try {
                return $this->attemptRefresh($id, $refresh);
            } catch (DriverException $exception) {
                if ($attempt >= self::MAX_ATTEMPTS || $exception->getSQLState() !== self::LOCK_NOT_AVAILABLE) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @param callable(StoredToken): ?AccessToken $refresh
     */
    private function attemptRefresh(UuidInterface $id, callable $refresh): AccessToken
    {
        $connection = $this->openConnection();

        try {
            return $this->refreshInTransaction($connection, $id, $refresh);
        } finally {
            $connection->close();
        }
    }

    /**
     * Connecting is retried on its own, and only here: being refused a connection is the
     * one failure that happens before anything has been read or spent, which makes
     * simply trying again unambiguously safe. Once the refresh is under way the same
     * error has to surface, because by then the provider may already have rotated the
     * refresh token and a second attempt cannot know whether it did.
     *
     * The connection is opened per refresh and dropped again rather than kept for the
     * service's lifetime. Refreshes are rare - one per token per expiry period - so a
     * handshake each time costs far less than every worker permanently holding a second
     * idle connection, which is what would make being refused one likelier to begin
     * with.
     */
    private function openConnection(): Connection
    {
        $attempt = 0;

        while (true) {
            ++$attempt;
            $connection = DriverManager::getConnection(['url' => $this->databaseUrl]);

            try {
                // DBAL connects lazily, so this first statement is also what establishes
                // the connection, and where being refused one surfaces
                $connection->executeStatement('SET lock_timeout = '.$this->lockTimeoutMs);

                return $connection;
            } catch (ConnectionException $exception) {
                $connection->close();

                if ($attempt >= self::MAX_CONNECT_ATTEMPTS) {
                    throw $exception;
                }

                usleep($this->connectRetryDelayMs * 1000);
            }
        }
    }

    /**
     * @param callable(StoredToken): ?AccessToken $refresh
     */
    private function refreshInTransaction(Connection $connection, UuidInterface $id, callable $refresh): AccessToken
    {
        $connection->beginTransaction();

        try {
            $stored = $this->lockAndRead($connection, $id);
            $refreshed = $refresh($stored);

            if ($refreshed !== null) {
                // reassigned to what was actually written, so the caller caches the same
                // expiry the next process will read back
                $refreshed = $this->store($connection, $id, $refreshed);
            }
        } catch (\Throwable $exception) {
            $this->rollBackQuietly($connection);

            throw $exception;
        }

        // deliberately outside the try: a failing commit is the one error that has to
        // arrive intact, because it is the case where the provider has already rotated
        // the refresh token and the stored one is now dead. DBAL unwinds its nesting
        // level only after the driver's COMMIT returns, so after a failed one the
        // transaction still looks active - attempting a rollback here would run against
        // an almost certainly dead connection and report its own error instead.
        $connection->commit();

        return $refreshed ?? $stored->asAccessToken();
    }

    /**
     * Best effort: the error that made the rollback necessary is the one worth
     * reporting, and the connection may already be gone by now.
     */
    private function rollBackQuietly(Connection $connection): void
    {
        try {
            $connection->rollBack();
        } catch (\Throwable $ignored) {
            // nothing left to do - the caller is about to see the original error
        }
    }

    private function lockAndRead(Connection $connection, UuidInterface $id): StoredToken
    {
        /** @var array{access_token: string, refresh_token: string|null, expires_at: string|null}|false $row */
        $row = $connection->fetchAssociative(
            'SELECT access_token, refresh_token, expires_at FROM user_oauth_token WHERE id = :id FOR UPDATE',
            ['id' => $id->toString()]
        );

        if ($row === false) {
            throw new \RuntimeException(sprintf('OAuth token %s no longer exists', $id->toString()));
        }

        return new StoredToken(
            $row['access_token'],
            $row['refresh_token'],
            $row['expires_at'] !== null ? new \DateTimeImmutable($row['expires_at']) : null
        );
    }

    /**
     * @return AccessToken the given token, carrying the expiry that was actually written
     */
    private function store(Connection $connection, UuidInterface $id, AccessToken $token): AccessToken
    {
        $expiresAt = $token->expiresAt() ?? (new \DateTimeImmutable())->setTimestamp(time() + self::ASSUMED_LIFETIME_SECONDS);

        $data = [
            'access_token' => $token->token(),
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ];

        // providers that do not rotate refresh tokens (e.g. GitHub) return none,
        // and the stored one has to stay in place
        if ($token->refreshToken() !== null) {
            $data['refresh_token'] = $token->refreshToken();
        }

        $connection->update('user_oauth_token', $data, ['id' => $id->toString()]);

        return new AccessToken($token->token(), $expiresAt, $token->refreshToken());
    }
}
