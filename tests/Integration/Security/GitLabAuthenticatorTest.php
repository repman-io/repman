<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Security;

use Buddy\Repman\Security\GitLabAuthenticator;
use Buddy\Repman\Tests\Doubles\GitLabOAuth;
use Buddy\Repman\Tests\Doubles\HttpClientStub;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use GuzzleHttp\Psr7\Response;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class GitLabAuthenticatorTest extends IntegrationTestCase
{
    public function testRedirectToLoginWithFlashOnFailure(): void
    {
        $response = $this->container()->get(GitLabAuthenticator::class)->onAuthenticationFailure(
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
        GitLabOAuth::mockTokenAndUserResponse('some@buddy.works', $this->container());
        $request = $this->createRequestWithSession();
        $request->attributes->set('_route', 'login_gitlab_check');
        $request->query->set('code', '123');
        $this->container()->get(ClientRegistry::class)->getClient('gitlab')->setAsStateless();

        $this->expectException(UserNotFoundException::class);

        $this->container()->get(GitLabAuthenticator::class)->authenticate($request);
    }

    public function testCustomMessageExceptionOnGitlabError(): void
    {
        $this->container()->get(HttpClientStub::class)->setNextResponses([new Response(429, [
            'RateLimit-Limit' => 600,
            'RateLimit-Remaining' => 0,
        ], '')]);

        $request = $this->createRequestWithSession();
        $request->attributes->set('_route', 'login_gitlab_check');
        $request->query->set('code', '123');
        $this->container()->get(ClientRegistry::class)->getClient('gitlab')->setAsStateless();

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Too Many Requests');

        $this->container()->get(GitLabAuthenticator::class)->authenticate($request);
    }
}
