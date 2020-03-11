<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity\User;

use Buddy\Repman\Entity\User\OAuthToken\ExpiredOAuthTokenException;
use Buddy\Repman\Tests\MotherObject\OAuthTokenMother;
use PHPUnit\Framework\TestCase;

final class OAuthTokenTest extends TestCase
{
    public function testRefreshTokenNotExist(): void
    {
        $token = OAuthTokenMother::withoutRefreshToken();

        $this->expectException(\RuntimeException::class);

        $token->refreshToken();
    }

    /**
     * @dataProvider expiredTimeProvider
     */
    public function testExpiredAccessToken(string $modifyTime): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify($modifyTime));

        $this->expectException(ExpiredOAuthTokenException::class);

        $token->accessToken();
    }

    public function testAccessTokenWithFutureExpirationDate(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('61 sec'));

        self::assertEquals('token', $token->accessToken());
    }

    public function testRefreshTokenWithExpireTime(): void
    {
        $token = OAuthTokenMother::withoutRefreshToken();
        self::assertEquals('token', $token->accessToken());

        $token->refresh('new', (new \DateTimeImmutable())->modify('-1 hour'));

        $this->expectException(ExpiredOAuthTokenException::class);

        $token->accessToken();
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
            ['1 min'],
        ];
    }
}
