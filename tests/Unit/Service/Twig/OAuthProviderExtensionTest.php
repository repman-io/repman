<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Twig;

use Buddy\Repman\Service\Twig\OAuthProviderExtension;
use PHPUnit\Framework\TestCase;

final class OAuthProviderExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        self::assertEquals('oauth_enabled', (new OAuthProviderExtension([]))->getFunctions()[0]->getName());
    }

    public function testAnyOauthProviderEnabled(): void
    {
        self::assertFalse((new OAuthProviderExtension([]))->oAuthEnabled());
        self::assertTrue((new OAuthProviderExtension(['github' => 'code']))->oAuthEnabled());
    }

    public function testGivenOauthProviderEnabled(): void
    {
        self::assertFalse((new OAuthProviderExtension([]))->oAuthEnabled('github'));
        self::assertFalse((new OAuthProviderExtension(['github' => '']))->oAuthEnabled('github'));
        self::assertFalse((new OAuthProviderExtension(['github' => null]))->oAuthEnabled('github'));
        self::assertTrue((new OAuthProviderExtension(['github' => 'code']))->oAuthEnabled('github'));
    }
}
