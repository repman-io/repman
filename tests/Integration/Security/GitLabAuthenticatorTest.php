<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Buddy\Repman\Security\GitLabAuthenticator;
use Buddy\Repman\Security\UserProvider;
use Buddy\Repman\Tests\Doubles\GitLabOAuth;
use Buddy\Repman\Tests\Doubles\HttpClientStub;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
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
        self::assertTrue($this->container()->get('session')->getFlashBag()->has('danger'));
    }

    public function testThrowExceptionIfUserWasNotFound(): void
    {
        GitLabOAuth::mockUserResponse('some@buddy.works', $this->container());

        $this->expectException(UsernameNotFoundException::class);

        $this->container()->get(GitLabAuthenticator::class)->getUser(
            new AccessToken(['access_token' => 'token']),
            $this->container()->get(UserProvider::class)
        );
    }

    public function testCustomMessageExceptionOnGitlabError(): void
    {
        $this->container()->get(HttpClientStub::class)->setNextResponses([new Response(429, [
            'RateLimit-Limit' => 600,
            'RateLimit-Remaining' => 0,
        ], '')]);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Too Many Requests');

        $this->container()->get(GitLabAuthenticator::class)->getUser(
            new AccessToken(['access_token' => 'token']),
            $this->container()->get(UserProvider::class)
        );
    }
}
