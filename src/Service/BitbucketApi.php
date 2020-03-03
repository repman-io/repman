<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

interface BitbucketApi
{
    public function primaryEmail(string $accessToken): string;
}
