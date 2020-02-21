<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

interface GitHubApi
{
    public function primaryEmail(string $accessToken): string;
}
