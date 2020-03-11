<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\RefreshOAuthToken;
use Buddy\Repman\Repository\UserRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Grant\RefreshToken;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RefreshOAuthTokenHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private ClientRegistry $oauth;

    public function __construct(UserRepository $users, ClientRegistry $oauth)
    {
        $this->users = $users;
        $this->oauth = $oauth;
    }

    public function __invoke(RefreshOAuthToken $message): void
    {
        $token = $this->users->getById(Uuid::fromString($message->userId()))->oauthToken($message->tokenType());
        if ($token === null || !$token->hasRefreshToken()) {
            return;
        }

        $accessToken = $this->oauth
            ->getClient($message->tokenType().'-package')
            ->getOAuth2Provider()
            ->getAccessToken(new RefreshToken(), ['refresh_token' => $token->refreshToken()])
        ;

        $token->refresh(
            $accessToken->getToken(),
            $accessToken->getExpires() !== null ? (new \DateTimeImmutable())->setTimestamp($accessToken->getExpires()) : null
        );
    }
}
