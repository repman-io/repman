<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\OAuth;

use Buddy\Repman\Service\OAuth\BuddyProvider;
use Buddy\Repman\Service\OAuth\BuddyResourceOwner;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;

final class BuddyProviderTest extends TestCase
{
    public function testAuthorizationUrlUsesPlusAsScopeSeparator(): void
    {
        $url = $this->provider()->getAuthorizationUrl(['scope' => [BuddyProvider::SCOPE_USER_INFO, BuddyProvider::SCOPE_USER_EMAIL]]);

        self::assertStringStartsWith('https://api.buddy.works/oauth2/authorize?', $url);
        self::assertStringContainsString('scope=USER_INFO%20USER_EMAIL', $url);
    }

    public function testCustomApiUrl(): void
    {
        self::assertSame('https://buddy.example.com/oauth2/token', (new BuddyProvider(['baseApiUrl' => 'https://buddy.example.com/']))->getBaseAccessTokenUrl([]));
    }

    public function testExchangesCodeAndLoadsUser(): void
    {
        $provider = $this->provider([
            new Response(200, ['Content-Type' => 'application/json'], '{"access_token":"token","token_type":"bearer"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":42,"name":"Jane"}'),
        ]);

        $token = $provider->getAccessToken('authorization_code', ['code' => 'abc']);
        self::assertInstanceOf(AccessToken::class, $token);
        $owner = $provider->getResourceOwner($token);

        self::assertInstanceOf(BuddyResourceOwner::class, $owner);
        self::assertSame(42, $owner->getId());
        self::assertSame(['id' => 42, 'name' => 'Jane'], $owner->toArray());
    }

    public function testErrorMessageFromBuddy(): void
    {
        $provider = $this->provider([new Response(400, ['Content-Type' => 'application/json'], '{"errors":[{"message":"Invalid code"}]}')]);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('Invalid code');

        $provider->getAccessToken('authorization_code', ['code' => 'abc']);
    }

    /**
     * @param Response[] $responses
     */
    private function provider(array $responses = []): BuddyProvider
    {
        return new BuddyProvider(
            ['clientId' => 'id', 'clientSecret' => 'secret', 'redirectUri' => 'https://repman.wip/auth/buddy/check'],
            ['httpClient' => new Client(['handler' => HandlerStack::create(new MockHandler($responses))])]
        );
    }
}
