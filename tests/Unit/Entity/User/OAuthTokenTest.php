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

    public function testAccessTokenDoesUpdateRefreshToken(): void
    {
        $nowMinusOneDay = (new \DateTimeImmutable())->modify('-1 day');
        $token = OAuthTokenMother::withExpireTime($nowMinusOneDay);
        $this->refresher->method('refresh')->withConsecutive(
            ['github', 'refresh'],
            ['github', 'new-refresh-token1'],
            ['github', 'new-refresh-token1']
        )->willReturnOnConsecutiveCalls(
            // On second call, "new-refresh-token1" should be used to refresh the token
            new AccessToken('new-token1', 'new-refresh-token1', $nowMinusOneDay),
            // Do not update the refresh token if its not provided by the oauth refresh endpoint
            new AccessToken('new-token2', null, $nowMinusOneDay),
            new AccessToken('new-token3')
        );

        self::assertEquals('new-token1', $token->accessToken($this->refresher));
        self::assertEquals('new-token2', $token->accessToken($this->refresher));
        self::assertEquals('new-token3', $token->accessToken($this->refresher));
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
