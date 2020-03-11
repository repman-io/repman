<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OauthToken;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class OauthTokenTest extends TestCase
{
    public function testRefreshTokenNotExist(): void
    {
        $token = new OauthToken(Uuid::uuid4(), new User(Uuid::uuid4(), 'test@buddy.works', 'token', []), OauthToken::TYPE_BITBUCKET, 'access-token');

        $this->expectException(\RuntimeException::class);

        $token->refreshToken();
    }
}
