<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Bitbucket\Exception\ApiLimitExceededException;
use Buddy\Repman\Security\BitbucketAuthenticator;
use Buddy\Repman\Service\Integration\BitbucketApi;
use Buddy\Repman\Tests\Doubles\BitbucketOAuth;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class BitbucketAuthenticatorTest extends IntegrationTestCase
{
    public function testRedirectToLoginWithFlashOnFailure(): void
    {
        $response = $this->container()->get(BitbucketAuthenticator::class)->onAuthenticationFailure(
            $request = $this->createRequestWithSession(),
            new UserNotFoundException()
        );

        self::assertTrue($response->isRedirection());

        $session = $request->getSession();
        self::assertInstanceOf(Session::class, $session);
        self::assertTrue($session->getFlashBag()->has('danger'));
    }

    public function testThrowExceptionIfUserWasNotFound(): void
    {
        BitbucketOAuth::mockAccessTokenResponse('some@buddy.works', $this->container());
        $this->expectException(UserNotFoundException::class);

        $request = $this->createRequestWithSession();
        $request->attributes->set('_route', 'login_bitbucket_check');
        $request->query->set('code', '123');
        $this->container()->get(ClientRegistry::class)->getClient('bitbucket')->setAsStateless();

        $this->container()->get(BitbucketAuthenticator::class)->authenticate($request);
    }

    public function testThrowCustomExceptionOnBitbucketApiError(): void
    {
        BitbucketOAuth::mockAccessTokenResponse('some@buddy.works', $this->container());
        $this->container()->get(BitbucketApi::class)->setExceptionOnNextCall(new ApiLimitExceededException('Message from Bitbucket about API limits'));

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Message from Bitbucket about API limits');

        $request = $this->createRequestWithSession();
        $request->attributes->set('_route', 'login_bitbucket_check');
        $request->query->set('code', '123');
        $this->container()->get(ClientRegistry::class)->getClient('bitbucket')->setAsStateless();

        $this->container()->get(BitbucketAuthenticator::class)->authenticate($request);
    }
}
