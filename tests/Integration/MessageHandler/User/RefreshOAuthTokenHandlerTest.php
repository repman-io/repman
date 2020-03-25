<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\User;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Message\User\RefreshOAuthToken;
use Buddy\Repman\MessageHandler\User\RefreshOAuthTokenHandler;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;

final class RefreshOAuthTokenHandlerTest extends IntegrationTestCase
{
    public function testUserWithoutRefreshToken(): void
    {
        $userId = $this->fixtures->createUser();
        $this->fixtures->createOauthToken($userId, OAuthToken::TYPE_GITHUB, 'token');

        $handler = $this->container()->get(RefreshOAuthTokenHandler::class);
        $handler->__invoke(new RefreshOAuthToken($userId, OAuthToken::TYPE_GITHUB));

        /** @var OAuthToken $token */
        $token = $this->container()->get(UserRepository::class)->getById(Uuid::fromString($userId))->oauthToken(OAuthToken::TYPE_GITHUB);
        self::assertFalse($token->hasRefreshToken());
    }
}
