<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration;

use Buddy\Repman\Service\Integration\BitbucketApi\Repositories;

interface BitbucketApi
{
    public function primaryEmail(string $accessToken): string;

    public function repositories(string $accessToken): Repositories;

    public function addHook(string $accessToken, string $fullName, string $hookUrl): void;

    public function removeHook(string $accessToken, string $fullName, string $hookUrl): void;
}
