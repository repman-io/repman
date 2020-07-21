<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

interface TelemetryQuery
{
    public function allOrganizationsCount(\DateTimeImmutable $date): int;

    public function publicOrganizationsCount(\DateTimeImmutable $date): int;

    public function allPackagesCount(\DateTimeImmutable $date): int;

    public function allPackagesInstalls(\DateTimeImmutable $date): int;

    public function allTokensCount(\DateTimeImmutable $date): int;

    public function allUsersCount(\DateTimeImmutable $date): int;
}
