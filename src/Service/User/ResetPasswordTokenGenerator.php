<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User;

use Ramsey\Uuid\Uuid;

final class ResetPasswordTokenGenerator
{
    public function generate(): string
    {
        return Uuid::uuid4()->toString();
    }
}
