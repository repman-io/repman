<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity\User;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\AccessToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\MissingRefreshTokenException;
use Buddy\Repman\Tests\MotherObject\OAuthTokenMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OAuthTokenTest extends TestCase
{
    /**
     * @var UserOAuthTokenRefresher|MockObject
     */
    private $refresher;

    protected function setUp(): void
    {
        $this->refresher = $this->createMock(UserOAuthTokenRefresher::class);
    }

    /**
     * @dataProvider expiredTimeProvider
     */
    public function testExpiredAccessToken(string $modifyTime): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify($modifyTime));
        $this->refresher->method('refresh')->willReturn(new AccessToken('new-token'));

        self::assertEquals('new-token', $token->accessToken($this->refresher));
    }

    public function testAccessTokenWithFutureExpirationDate(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('61 sec'));

        self::assertEquals('token', $token->accessToken($this->refresher));
    }

    public function testRefreshedTokenIsReusedWhileStillValid(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('-1 day'));

        $this->refresher->expects(self::once())
            ->method('refresh')
            ->with($token->id(), OAuthToken::TYPE_GITHUB)
            ->willReturn(new AccessToken('new-token', (new \DateTimeImmutable())->modify('1 hour')));

        self::assertEquals('new-token', $token->accessToken($this->refresher));
        self::assertEquals('new-token', $token->accessToken($this->refresher));
    }

    public function testRefreshedTokenIsRefreshedAgainOnceItExpires(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('-1 day'));

        $this->refresher->expects(self::exactly(2))
            ->method('refresh')
            ->with($token->id(), OAuthToken::TYPE_GITHUB)
            ->willReturnOnConsecutiveCalls(
                new AccessToken('first-token', (new \DateTimeImmutable())->modify('30 sec')),
                new AccessToken('second-token', (new \DateTimeImmutable())->modify('1 hour'))
            );

        self::assertEquals('first-token', $token->accessToken($this->refresher));
        // the minimum interval between refreshes has to let go again, or a worker
        // outliving one token would keep using it well past its real expiry
        $this->ageLastRefresh($token, '-31 sec');
        self::assertEquals('second-token', $token->accessToken($this->refresher));
    }

    /**
     * A provider can hand back a token that is already inside the expiration margin - an
     * expires_in of a minute or less does exactly that - and asking again straight away
     * cannot produce a better one. ComposerPackageSynchronizer asks twice per package
     * version, so without a floor one sync of a few hundred versions would mean a few
     * hundred provider round trips, each rotating the refresh token.
     */
    public function testFreshlyRefreshedTokenIsNotRefreshedAgainImmediately(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('-1 day'));

        $this->refresher->expects(self::once())
            ->method('refresh')
            ->willReturn(new AccessToken('new-token', (new \DateTimeImmutable())->modify('30 sec')));

        for ($call = 0; $call < 5; ++$call) {
            self::assertEquals('new-token', $token->accessToken($this->refresher));
        }
    }

    /**
     * The floor above holds a token the margin counts as expired, because such a token
     * is still usable. One that is genuinely past its expiry is not: handing it out
     * would make every call for the rest of the interval fail with an opaque 401 from
     * the provider, so what happened has to be visible instead.
     */
    public function testTokenThatArrivesAlreadyExpiredIsReportedRatherThanUsed(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('-1 day'));

        // once: the provider is not asked again inside the interval, which is what the
        // floor is for - a second request could only spend another rotation
        $this->refresher->expects(self::once())
            ->method('refresh')
            ->willReturn(new AccessToken('dead-token', (new \DateTimeImmutable())->modify('-1 sec')));

        self::assertSame('dead-token', $token->accessToken($this->refresher));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('had already expired at');

        $token->accessToken($this->refresher);
    }

    /**
     * The report above lasts only as long as the interval that suppresses the refresh:
     * once it passes, an ordinary refresh takes over and can recover on its own.
     */
    public function testTokenThatArrivedExpiredIsRefreshedAgainOnceTheIntervalPasses(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('-1 day'));

        $this->refresher->expects(self::exactly(2))
            ->method('refresh')
            ->willReturnOnConsecutiveCalls(
                new AccessToken('dead-token', (new \DateTimeImmutable())->modify('-1 sec')),
                new AccessToken('live-token', (new \DateTimeImmutable())->modify('1 hour'))
            );

        self::assertSame('dead-token', $token->accessToken($this->refresher));
        $this->ageLastRefresh($token, '-31 sec');

        self::assertSame('live-token', $token->accessToken($this->refresher));
    }

    public function testErrorDuringRefresh(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('-1 day'));
        $this->refresher->method('refresh')->willThrowException(new \RuntimeException('invalid refresh_token'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid refresh_token/');

        $token->accessToken($this->refresher);
    }

    public function testDataStateErrorKeepsItsTypeInsteadOfLookingLikeATransientFailure(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('-1 day'));
        $this->refresher->method('refresh')->willThrowException(new MissingRefreshTokenException('Unable to refresh access token without refresh token'));

        $this->expectException(MissingRefreshTokenException::class);
        $this->expectExceptionMessage('without refresh token');

        $token->accessToken($this->refresher);
    }

    /**
     * The OAuth and HTTP libraries raise \InvalidArgumentException - a \LogicException -
     * on malformed responses and bad option arrays, and that is an ordinary refresh
     * failure: it has to keep the framing rather than reach the caller as a bare library
     * message.
     */
    public function testLibraryArgumentErrorIsReportedAsARefreshFailure(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('-1 day'));
        $this->refresher->method('refresh')->willThrowException(new \InvalidArgumentException('Magic request methods require a URI and optional options array'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('An error occurred while refreshing the access token');

        $token->accessToken($this->refresher);
    }

    /**
     * Stands in for time passing since the last refresh, which nothing but the clock
     * moves in production.
     */
    private function ageLastRefresh(OAuthToken $token, string $modify): void
    {
        $property = (new \ReflectionObject($token))->getProperty('refreshedAt');
        $property->setAccessible(true);
        $property->setValue($token, (new \DateTimeImmutable())->modify($modify));
    }

    /**
     * @return mixed[]
     */
    public function expiredTimeProvider(): array
    {
        return [
            ['-1 hour'],
            ['0 sec'],
            ['9 sec'],
            // a second short of the margin boundary on purpose: exactly on it, the row
            // would hold only because the clock advances mid-test
            ['59 sec'],
        ];
    }
}
