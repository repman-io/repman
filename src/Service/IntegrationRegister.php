<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\Integration\BitbucketApi;
use Buddy\Repman\Service\Integration\GitHubApi;
use Buddy\Repman\Service\Integration\GitLabApi;

class IntegrationRegister
{
    private GitHubApi $gitHubApi;
    private GitLabApi $gitLabApi;
    private BitbucketApi $bitbucketApi;

    public function __construct(GitHubApi $gitHubApi, GitLabApi $gitLabApi, BitbucketApi $bitbucketApi)
    {
        $this->gitHubApi = $gitHubApi;
        $this->gitLabApi = $gitLabApi;
        $this->bitbucketApi = $bitbucketApi;
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
