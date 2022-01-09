<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Buddy\Repman\Security\GitHubAuthenticator;
use Buddy\Repman\Service\Integration\GitHubApi;
use Buddy\Repman\Tests\Doubles\GitHubOAuth;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Github\Exception\ApiLimitExceedException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class GitHubAuthenticatorTest extends IntegrationTestCase
{
    public function testRedirectToLoginWithFlashOnFailure(): void
    {
        $response = $this->container()->get(GitHubAuthenticator::class)->onAuthenticationFailure(
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
        GitHubOAuth::mockTokenResponse('some@buddy.works', $this->container());
        $request = $this->createRequestWithSession();
        $request->attributes->set('_route', 'login_github_check');
        $request->query->set('code', '123');
        $this->container()->get(ClientRegistry::class)->getClient('github')->setAsStateless();

        $this->expectException(UserNotFoundException::class);

        $this->container()->get(GitHubAuthenticator::class)->authenticate($request);
    }

    public function testThrowCustomExceptionOnGitHubApiError(): void
    {
        GitHubOAuth::mockTokenResponse('some@buddy.works', $this->container());
        $this->container()->get(GitHubApi::class)->setExceptionOnNextCall(new ApiLimitExceedException(5000));
        $request = $this->createRequestWithSession();
        $request->attributes->set('_route', 'login_github_check');
        $request->query->set('code', '123');
        $this->container()->get(ClientRegistry::class)->getClient('github')->setAsStateless();

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('You have reached GitHub hourly limit! Actual limit is: 5000');

        $this->container()->get(GitHubAuthenticator::class)->authenticate($request);
    }
}
