<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Buddy\Repman\Security\GitHubAuthenticator;
use Buddy\Repman\Tests\Doubles\GitHubOAuth;
use Buddy\Repman\Tests\Doubles\UserProviderStub;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

final class GitHubAuthenticatorTest extends IntegrationTestCase
{
    public function testRedirectToLoginWithFlashOnFailure(): void
    {
        $response = $this->container()->get(GitHubAuthenticator::class)->onAuthenticationFailure(
            Request::createFromGlobals(),
            new UsernameNotFoundException()
        );

        self::assertTrue($response->isRedirection());
        self::assertTrue($this->container()->get('session.flash_bag')->has('danger'));
    }

    public function testThrowExceptionIfUserWasNotFound(): void
    {
        GitHubOAuth::mockTokenResponse('some@buddy.works', $this->container());
        $this->expectException(UsernameNotFoundException::class);

        $this->container()->get(GitHubAuthenticator::class)->getUser(
            new AccessToken(['access_token' => 'token']),
            new UserProviderStub()
        );
    }
}
