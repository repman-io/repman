<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Buddy\Repman\Security\BuddyAuthenticator;
use Buddy\Repman\Security\UserProvider;
use Buddy\Repman\Service\BuddyApi;
use Buddy\Repman\Service\BuddyApi\BuddyApiException;
use Buddy\Repman\Tests\Doubles\BuddyOAuth;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

final class BuddyAuthenticatorTest extends IntegrationTestCase
{
    public function testRedirectToLoginWithFlashOnFailure(): void
    {
        $response = $this->container()->get(BuddyAuthenticator::class)->onAuthenticationFailure(
            Request::createFromGlobals(),
            new UsernameNotFoundException()
        );

        self::assertTrue($response->isRedirection());
        self::assertTrue($this->container()->get('session')->getFlashBag()->has('danger'));
    }

    public function testThrowExceptionIfUserWasNotFound(): void
    {
        BuddyOAuth::mockAccessTokenResponse('some@buddy.works', $this->container());
        $this->expectException(UsernameNotFoundException::class);

        $this->container()->get(BuddyAuthenticator::class)->getUser(
            new AccessToken(['access_token' => 'token']),
            $this->container()->get(UserProvider::class)
        );
    }

    public function testThrowCustomExceptionOnBitbucketApiError(): void
    {
        $this->container()->get(BuddyApi::class)->setExceptionOnNextCall(new BuddyApiException('Missing confirmed e-mail'));

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Missing confirmed e-mail');

        $this->container()->get(BuddyAuthenticator::class)->getUser(
            new AccessToken(['access_token' => 'token']),
            $this->container()->get(UserProvider::class)
        );
    }
}
