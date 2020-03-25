<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\User;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Message\User\RefreshOAuthToken;
use Buddy\Repman\MessageHandler\User\RefreshOAuthTokenHandler;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class RefreshOAuthTokenHandlerTest extends IntegrationTestCase
{
    public function testUserWithoutRefreshToken(): void
    {
        $this->expectNotToPerformAssertions();

        $userId = $this->fixtures->createUser();
        $this->fixtures->createOauthToken($userId, OAuthToken::TYPE_GITHUB, 'token');

        $handler = $this->container()->get(RefreshOAuthTokenHandler::class);
        $handler->__invoke(new RefreshOAuthToken($userId, OAuthToken::TYPE_GITHUB));
    }
}
