<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Buddy\Repman\Security\BuddyAuthenticator;
use Buddy\Repman\Service\Integration\BuddyApi;
use Buddy\Repman\Service\Integration\BuddyApi\BuddyApiException;
use Buddy\Repman\Tests\Doubles\BuddyOAuth;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class BuddyAuthenticatorTest extends IntegrationTestCase
{
    public function testRedirectToLoginWithFlashOnFailure(): void
    {
        $response = $this->container()->get(BuddyAuthenticator::class)->onAuthenticationFailure(
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
        BuddyOAuth::mockAccessTokenResponse('some@buddy.works', $this->container());
        $this->expectException(UserNotFoundException::class);

        $request = $this->createRequestWithSession();
        $request->attributes->set('_route', 'login_bitbucket_check');
        $request->query->set('code', '123');
        $this->container()->get(ClientRegistry::class)->getClient('buddy')->setAsStateless();

        $this->container()->get(BuddyAuthenticator::class)->authenticate($request);
    }

    public function testThrowCustomExceptionOnBitbucketApiError(): void
    {
        BuddyOAuth::mockAccessTokenResponse('some@buddy.works', $this->container());
        $this->container()->get(BuddyApi::class)->setExceptionOnNextCall(new BuddyApiException('Missing confirmed e-mail'));

        $request = $this->createRequestWithSession();
        $request->attributes->set('_route', 'login_bitbucket_check');
        $request->query->set('code', '123');
        $this->container()->get(ClientRegistry::class)->getClient('buddy')->setAsStateless();

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Missing confirmed e-mail');

        $this->container()->get(BuddyAuthenticator::class)->authenticate($request);
    }
}
