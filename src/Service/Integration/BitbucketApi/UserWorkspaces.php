<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\BitbucketApi;

use Bitbucket\Api\CurrentUser;

/**
 * GET /2.0/user/workspaces, which bitbucket/client does not cover yet.
 *
 * It is the one cross-workspace endpoint Bitbucket still serves: CHANGE-2770 withdrew
 * GET /2.0/repositories, GET /2.0/user/permissions/{repositories,workspaces} and
 * GET /2.0/workspaces on 2026-04-14, and Atlassian has said no cross-workspace
 * replacement for the repository listing is coming - "changes to our architecture is
 * what necessitated the deprecation of cross-workspace APIs in the first place". So
 * this is how the set of workspaces to ask about is discovered, one request each.
 *
 * Extends the library's own current-user endpoint rather than AbstractApi so the
 * /2.0/user/ prefix keeps coming from one place; when the library adds a method of its
 * own, this class goes away.
 */
final class UserWorkspaces extends CurrentUser
{
    /**
     * @param array<string, mixed> $params
     *
     * @return array<mixed>
     */
    public function list(array $params = []): array
    {
        return $this->get($this->buildCurrentUserUri('workspaces'), $params);
    }
}
