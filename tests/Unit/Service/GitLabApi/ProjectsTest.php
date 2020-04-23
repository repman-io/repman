<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\GitLabApi;

use Buddy\Repman\Service\GitLabApi\Project;
use Buddy\Repman\Service\GitLabApi\Projects;
use PHPUnit\Framework\TestCase;

final class ProjectsTest extends TestCase
{
    public function testReturnProjectsNames(): void
    {
        $projects = new Projects([
            new Project(1, 'first', 'url'),
            new Project(2, 'second', 'url'),
        ]);

        self::assertEquals([1 => 'first', 2 => 'second'], $projects->names());

        $projects = new Projects([]);
        self::assertEquals([], $projects->names());
    }

    public function testThrowExceptionWhenProjectNotFound(): void
    {
        $projects = new Projects([
            $first = new Project(1, 'first', 'url'),
        ]);

        $this->expectException(\RuntimeException::class);
        $projects->get(666);
    }
}
