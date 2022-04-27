<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\BitbucketApi;

final class Repositories
{
    /**
     * @var array<string,Repository>|Repository[]
     */
    private array $repos = [];

    /**
     * @param Repository[] $repos
     */
    public function __construct(array $repos)
    {
        foreach ($repos as $repo) {
            $this->repos[$repo->uuid()] = $repo;
        }
    }

    /**
     * @return array<string,string>
     */
    public function names(): array
    {
        $names = [];
        foreach ($this->repos as $repo) {
            $names[$repo->uuid()] = $repo->name();
        }

        return $names;
    }

    public function get(string $uuid): Repository
    {
        if (!isset($this->repos[$uuid])) {
            throw new \RuntimeException(sprintf('Repository %s not found', $uuid));
        }

        return $this->repos[$uuid];
    }
}
