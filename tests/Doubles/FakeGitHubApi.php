<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\GitHubApi;

final class FakeGitHubApi implements GitHubApi
{
    private string $primaryEmail = '';

    public function primaryEmail(string $accessToken): string
    {
        return $this->primaryEmail;
    }

    public function setPrimaryEmail(string $primaryEmail): void
    {
        $this->primaryEmail = $primaryEmail;
    }
}
