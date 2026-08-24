<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\User\OAuthTokenStore;

use Buddy\Repman\Service\User\OAuthTokenStore\StoredToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\AccessToken;
use PHPUnit\Framework\TestCase;

final class StoredTokenTest extends TestCase
{
    /**
     * @dataProvider expiredTimeProvider
     */
    public function testTokenAboutToExpireCountsAsExpired(string $modifyTime): void
    {
        self::assertTrue((new StoredToken('token', 'refresh', (new \DateTimeImmutable())->modify($modifyTime)))->isExpired());
    }

    public function testTokenWithFutureExpirationDateIsNotExpired(): void
    {
        self::assertFalse((new StoredToken('token', 'refresh', (new \DateTimeImmutable())->modify('61 sec')))->isExpired());
    }

    public function testTokenWithoutExpirationDateNeverExpires(): void
    {
        self::assertFalse((new StoredToken('token', 'refresh'))->isExpired());
    }

    public function testConversionToAccessTokenKeepsRefreshToken(): void
    {
        $expiresAt = (new \DateTimeImmutable())->modify('1 hour');

        self::assertEquals(
            new AccessToken('token', $expiresAt, 'refresh'),
            (new StoredToken('token', 'refresh', $expiresAt))->asAccessToken()
        );
    }

    /**
     * The last row is the interesting one: still most of a minute of real life left, and
     * counted as expired anyway because of the margin. It stops a second short of the
     * boundary deliberately - a row sitting exactly on it holds only because a few
     * microseconds pass between constructing the token and asking, so it would assert
     * that the clock advances rather than anything about the margin.
     *
     * @return array<array<string>>
     */
    public function expiredTimeProvider(): array
    {
        return [
            ['-1 hour'],
            ['0 sec'],
            ['9 sec'],
            ['59 sec'],
        ];
    }
}
