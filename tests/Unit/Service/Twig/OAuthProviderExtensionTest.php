<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Twig;

use Buddy\Repman\Service\Twig\OAuthProviderExtension;
use PHPUnit\Framework\TestCase;

final class OAuthProviderExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        $this->assertSame('oauth_enabled', (new OAuthProviderExtension([]))->getFunctions()[0]->getName());
    }

    public function testAnyOauthProviderEnabled(): void
    {
        $this->assertFalse((new OAuthProviderExtension([]))->oAuthEnabled());
        $this->assertTrue((new OAuthProviderExtension(['github' => 'code']))->oAuthEnabled());
    }

    public function testGivenOauthProviderEnabled(): void
    {
        $this->assertFalse((new OAuthProviderExtension([]))->oAuthEnabled('github'));
        $this->assertFalse((new OAuthProviderExtension(['github' => '']))->oAuthEnabled('github'));
        $this->assertFalse((new OAuthProviderExtension(['github' => null]))->oAuthEnabled('github'));
        $this->assertTrue((new OAuthProviderExtension(['github' => 'code']))->oAuthEnabled('github'));
    }
}
