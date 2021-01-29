<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration;

interface BuddyApi
{
    public function primaryEmail(string $accessToken): string;
}
