<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\GitLabApi;

final class Projects
{
    /**
     * @var array<int,Project>|Project[]
     */
    private array $projects = [];

    /**
     * @param Project[] $projects
     */
    public function __construct(array $projects)
    {
        foreach ($projects as $project) {
            $this->projects[$project->id()] = $project;
        }
    }

    /**
     * @return array<int,string>
     */
    public function names(): array
    {
        $names = [];
        foreach ($this->projects as $project) {
            $names[$project->id()] = $project->name();
        }

        return $names;
    }

    public function get(int $id): Project
    {
        if (!isset($this->projects[$id])) {
            throw new \RuntimeException(sprintf('Project %s not found', $id));
        }

        return $this->projects[$id];
    }
}
