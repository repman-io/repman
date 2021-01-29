<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity\User;

use Buddy\Repman\Service\User\UserOAuthTokenRefresher;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\AccessToken;
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

    public function testErrorDuringRefresh(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('-1 day'));
        $this->refresher->method('refresh')->willThrowException(new \RuntimeException('invalid refresh_token'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid refresh_token/');

        $token->accessToken($this->refresher);
    }

    public function testErrorWhenMissingRefreshToken(): void
    {
        $token = OAuthTokenMother::withoutRefreshToken((new \DateTimeImmutable())->modify('-1 day'));

        $this->expectException(\LogicException::class);

        $token->accessToken($this->refresher);
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
