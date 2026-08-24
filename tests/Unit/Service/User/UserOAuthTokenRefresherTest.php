<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\User;

use Buddy\Repman\Service\User\OAuthTokenStore;
use Buddy\Repman\Service\User\OAuthTokenStore\StoredToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\AccessToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\MissingRefreshTokenException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken as LeagueAccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UserOAuthTokenRefresherTest extends TestCase
{
    /**
     * @var AbstractProvider|MockObject
     */
    private $provider;

    private UuidInterface $tokenId;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(AbstractProvider::class);
        $this->tokenId = Uuid::uuid4();
    }

    public function testRefreshToken(): void
    {
        $this->provider->method('getAccessToken')->willReturn(
            new LeagueAccessToken(['access_token' => 'new-token', 'expires_in' => 3600])
        );
        $refresher = $this->refresherFor($this->expiredStoredToken('refresh-token'));

        self::assertEquals(
            new AccessToken('new-token', (new \DateTimeImmutable())->setTimestamp(time() + 3600)),
            $refresher->refresh($this->tokenId, 'github')
        );
    }

    /**
     * An absent expires_in is passed on as the absent value it is, rather than guessed
     * at here: what gets recorded for it is the store's call, made where it cannot be
     * bypassed. OAuthTokenStoreTest covers the short lifetime it assumes.
     */
    public function testResponseWithoutExpiresInIsReportedWithoutOne(): void
    {
        $this->provider->method('getAccessToken')->willReturn(
            new LeagueAccessToken(['access_token' => 'new-token', 'refresh_token' => 'rotated-refresh-token'])
        );
        $refresher = $this->refresherFor($this->expiredStoredToken('old-refresh-token'));

        self::assertNull($refresher->refresh($this->tokenId, 'bitbucket')->expiresAt());
    }

    public function testRotatedRefreshTokenIsReturned(): void
    {
        $this->provider->method('getAccessToken')->willReturn(
            new LeagueAccessToken(['access_token' => 'new-token', 'refresh_token' => 'rotated-refresh-token', 'expires_in' => 7200])
        );
        $refresher = $this->refresherFor($this->expiredStoredToken('old-refresh-token'));

        self::assertEquals(
            new AccessToken('new-token', (new \DateTimeImmutable())->setTimestamp(time() + 7200), 'rotated-refresh-token'),
            $refresher->refresh($this->tokenId, 'bitbucket')
        );
    }

    public function testRefreshTokenIsTakenFromStorageNotFromTheCaller(): void
    {
        $this->provider->expects(self::once())
            ->method('getAccessToken')
            ->with(new RefreshToken(), ['refresh_token' => 'rotated-by-other-worker'])
            ->willReturn(new LeagueAccessToken(['access_token' => 'new-token', 'refresh_token' => 'rotated-again', 'expires_in' => 7200]));
        $refresher = $this->refresherFor($this->expiredStoredToken('rotated-by-other-worker'));

        self::assertEquals(
            new AccessToken('new-token', (new \DateTimeImmutable())->setTimestamp(time() + 7200), 'rotated-again'),
            $refresher->refresh($this->tokenId, 'bitbucket')
        );
    }

    public function testMissingStoredRefreshTokenIsReported(): void
    {
        $this->provider->expects(self::never())->method('getAccessToken');
        $refresher = $this->refresherFor(new StoredToken('expired-token', null, (new \DateTimeImmutable())->modify('-1 hour')));

        $this->expectException(MissingRefreshTokenException::class);
        $this->expectExceptionMessage('without refresh token');

        $refresher->refresh($this->tokenId, 'bitbucket');
    }

    public function testTokenAlreadyRefreshedByConcurrentWorkerIsReusedWithoutCallingProvider(): void
    {
        $this->provider->expects(self::never())->method('getAccessToken');
        $expiresAt = (new \DateTimeImmutable())->modify('2 hours');
        $refresher = $this->refresherFor(new StoredToken('token-from-other-worker', 'fresh-refresh-token', $expiresAt));

        self::assertEquals(
            new AccessToken('token-from-other-worker', $expiresAt, 'fresh-refresh-token'),
            $refresher->refresh($this->tokenId, 'bitbucket')
        );
    }

    private function expiredStoredToken(string $refreshToken): StoredToken
    {
        return new StoredToken('expired-token', $refreshToken, (new \DateTimeImmutable())->modify('-1 hour'));
    }

    /**
     * Stands in for the real store, which runs the callback under a row lock and
     * commits whatever it returns.
     */
    private function refresherFor(StoredToken $stored): UserOAuthTokenRefresher
    {
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->method('getOAuth2Provider')->willReturn($this->provider);
        $oauth = $this->createMock(ClientRegistry::class);
        $oauth->method('getClient')->willReturn($client);

        $store = $this->createMock(OAuthTokenStore::class);
        $store->method('refreshExclusively')->willReturnCallback(
            static fn (UuidInterface $id, callable $refresh): AccessToken => $refresh($stored) ?? $stored->asAccessToken()
        );

        return new UserOAuthTokenRefresher($oauth, $store);
    }
}
