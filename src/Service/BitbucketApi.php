<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\BitbucketApi\Repository;

interface BitbucketApi
{
    public function primaryEmail(string $accessToken): string;

    /**
     * @return Repository[]
     */
    public function repositories(string $accessToken): array;

    public function addHook(string $accessToken, string $fullName, string $hookUrl): void;
}
