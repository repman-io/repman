<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\MotherObject;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OAuthToken;
use Ramsey\Uuid\Uuid;

final class OAuthTokenMother
{
    public static function withoutRefreshToken(?\DateTimeImmutable $expireAt = null): OAuthToken
    {
        return new OAuthToken(Uuid::uuid4(), self::user(), OAuthToken::TYPE_GITHUB, 'token', null, $expireAt);
    }

    public static function withExpireTime(\DateTimeImmutable $expireAt): OAuthToken
    {
        return new OAuthToken(Uuid::uuid4(), self::user(), OAuthToken::TYPE_GITHUB, 'token', 'refresh', $expireAt);
    }

    private static function user(): User
    {
        return new User(Uuid::uuid4(), 'test@buddy.works', 'token', []);
    }
}
