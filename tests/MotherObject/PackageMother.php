<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\MotherObject;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OauthToken;
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
            new User(Uuid::uuid4(), 'test@buddy.works', 'confirm-token', []),
            'Buddy',
            $organizationAlias
        ));

        return $package;
    }

    public static function withOrganizationAndToken(string $type, string $url, string $organizationAlias): Package
    {
        $package = new Package(
            Uuid::uuid4(),
            $type,
            $url
        );
        $package->setOrganization(new Organization(
            Uuid::uuid4(),
            new User(Uuid::uuid4(), 'test@buddy.works', 'confirm-token', []),
            'Buddy',
            $organizationAlias
        ));
        $package->setOauthToken(
            new OauthToken(
                Uuid::uuid4(),
                new User(Uuid::uuid4(), 'test@buddy.works', 'confirm-token', []),
                'github',
                'secret'
            )
        );

        return $package;
    }
}
