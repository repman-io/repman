<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity\User;

use Buddy\Repman\Tests\MotherObject\OAuthTokenMother;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OAuthTokenTest extends TestCase
{
    /**
     * @var ClientRegistry|MockObject
     */
    private $oauth;

    /**
     * @var AbstractProvider|MockObject
     */
    private $provider;

    protected function setUp(): void
    {
        $this->oauth = $this->createMock(ClientRegistry::class);
        $this->provider = $this->createMock(AbstractProvider::class);
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->method('getOAuth2Provider')->willReturn($this->provider);
        $this->oauth->method('getClient')->willReturn($client);
    }

    /**
     * @dataProvider expiredTimeProvider
     */
    public function testExpiredAccessToken(string $modifyTime): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify($modifyTime));
        $this->provider->method('getAccessToken')->willReturn(new AccessToken(['access_token' => 'new-token']));

        self::assertEquals('new-token', $token->accessToken($this->oauth));
    }

    public function testAccessTokenWithFutureExpirationDate(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('61 sec'));

        self::assertEquals('token', $token->accessToken($this->oauth));
    }

    public function testErrorDuringRefresh(): void
    {
        $token = OAuthTokenMother::withExpireTime((new \DateTimeImmutable())->modify('-1 day'));
        $this->provider->method('getAccessToken')->willThrowException(new \RuntimeException('invalid refresh_token'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid refresh_token/');

        $token->accessToken($this->oauth);
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
