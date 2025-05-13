<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\Integration\BitbucketApi;
use Buddy\Repman\Service\Integration\GitHubApi;
use Buddy\Repman\Service\Integration\GitLabApi;

class IntegrationRegister
{
    public function __construct(private readonly GitHubApi $gitHubApi, private readonly GitLabApi $gitLabApi, private readonly BitbucketApi $bitbucketApi)
    {
    }

    public function gitHubApi(): GitHubApi
    {
        return $this->gitHubApi;
    }

    public function gitLabApi(): GitLabApi
    {
        return $this->gitLabApi;
    }

    public function bitbucketApi(): BitbucketApi
    {
        return $this->bitbucketApi;
    }
}
