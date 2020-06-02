<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Bitbucket\Exception\ApiLimitExceededException;
use Buddy\Repman\Security\BitbucketAuthenticator;
use Buddy\Repman\Security\UserProvider;
use Buddy\Repman\Service\BitbucketApi;
use Buddy\Repman\Tests\Doubles\BitbucketOAuth;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

final class BitbucketAuthenticatorTest extends IntegrationTestCase
{
    public function testRedirectToLoginWithFlashOnFailure(): void
    {
        $response = $this->container()->get(BitbucketAuthenticator::class)->onAuthenticationFailure(
            Request::createFromGlobals(),
            new UsernameNotFoundException()
        );

        self::assertTrue($response->isRedirection());
        self::assertTrue($this->container()->get('session')->getFlashBag()->has('danger'));
    }

    public function testThrowExceptionIfUserWasNotFound(): void
    {
        BitbucketOAuth::mockAccessTokenResponse('some@buddy.works', $this->container());
        $this->expectException(UsernameNotFoundException::class);

        $this->container()->get(BitbucketAuthenticator::class)->getUser(
            new AccessToken(['access_token' => 'token']),
            $this->container()->get(UserProvider::class)
        );
    }

    public function testThrowCustomExceptionOnBitbucketApiError(): void
    {
        $this->container()->get(BitbucketApi::class)->setExceptionOnNextCall(new ApiLimitExceededException('Message from Bitbucket about API limits'));

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Message from Bitbucket about API limits');

        $this->container()->get(BitbucketAuthenticator::class)->getUser(
            new AccessToken(['access_token' => 'token']),
            $this->container()->get(UserProvider::class)
        );
    }
}
