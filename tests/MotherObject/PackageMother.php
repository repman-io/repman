<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\MotherObject;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\User;
use Ramsey\Uuid\Uuid;

final class PackageMother
{
    public static function some(string $type = 'vcs', string $url = 'https://github.com/buddy-works/repman'): Package
    {
        return new Package(Uuid::uuid4(), $type, $url);
    }

    public static function withOrganization(string $type, string $url, string $organizationAlias): Package
    {
        $package = new Package(Uuid::uuid4(), $type, $url);
        $package->setOrganization(new Organization(
            Uuid::uuid4(),
            new User(Uuid::uuid4(), 'test@buddy.works', 'confir-token', []),
            'Buddy',
            $organizationAlias
        ));

        return $package;
    }
}
