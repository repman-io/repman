<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Buddy\Repman\Security\GitLabAuthenticator;
use Buddy\Repman\Tests\Doubles\GitLabOAuth;
use Buddy\Repman\Tests\Doubles\UserProviderStub;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

final class GitLabAuthenticatorTest extends IntegrationTestCase
{
    public function testRedirectToLoginWithFlashOnFailure(): void
    {
        $response = $this->container()->get(GitLabAuthenticator::class)->onAuthenticationFailure(
            Request::createFromGlobals(),
            new UsernameNotFoundException()
        );

        self::assertTrue($response->isRedirection());
        self::assertTrue($this->container()->get('session.flash_bag')->has('danger'));
    }

    public function testThrowExceptionIfUserWasNotFound(): void
    {
        GitLabOAuth::mockTokenAndUserResponse('some@buddy.works', $this->container());

        $this->expectException(UsernameNotFoundException::class);

        $this->container()->get(GitLabAuthenticator::class)->getUser(
            new AccessToken(['access_token' => 'token']),
            new UserProviderStub()
        );
    }
}
