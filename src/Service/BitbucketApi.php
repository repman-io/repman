<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\BitbucketApi\Repositories;

interface BitbucketApi
{
    public function primaryEmail(string $accessToken): string;

    public function repositories(string $accessToken): Repositories;

    public function addHook(string $accessToken, string $fullName, string $hookUrl): void;
}
