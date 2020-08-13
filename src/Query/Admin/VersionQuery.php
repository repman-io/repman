<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

interface VersionQuery
{
    public function oldDistsCount(): int;

    /**
     * @return array<array<string,string>>
     */
    public function findPackagesWithDevVersions(int $limit = 100, int $offset = 0): array;

    /**
     * @return array<array<string,string>>
     */
    public function findPackagesDevVersions(string $packageId): array;
}
