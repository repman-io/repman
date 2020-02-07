<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization\TokenGenerator;

use Buddy\Repman\Service\Organization\TokenGenerator;

final class RandomTokenGenerator implements TokenGenerator
{
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }
}
