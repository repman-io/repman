<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\MotherObject;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OAuthToken;
use Ramsey\Uuid\Uuid;

final class PackageMother
{
    public static function some(string $type = 'vcs', string $url = 'https://github.com/buddy-works/repman'): Package
    {
        return new Package(Uuid::uuid4(), $type, $url);
    }

    public static function withOrganization(string $type, string $url, string $organizationAlias, int $keepLastReleases = 0): Package
    {
        $package = new Package(Uuid::uuid4(), $type, $url, [], $keepLastReleases);
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
            "$type-oauth",
            $url
        );
        $package->setOrganization(new Organization(
            Uuid::uuid4(),
            $user = new User(Uuid::uuid4(), 'test@buddy.works', 'confirm-token', []),
            'Buddy',
            $organizationAlias
        ));
        $user->addOAuthToken(new OAuthToken(
            Uuid::uuid4(),
            new User(Uuid::uuid4(), 'test@buddy.works', 'confirm-token', []),
            $type,
            'secret'
        ));

        return $package;
    }

    /**
     * @param array<string,bool> $unencounteredVersions
     * @param array<string,bool> $unencounteredLinks
     */
    public static function synchronized(string $name, string $latestVersion, string $url = '', array $unencounteredVersions = [], array $unencounteredLinks = []): Package
    {
        $package = new Package(Uuid::uuid4(), 'path', $url);
        $package->setOrganization(new Organization(
            Uuid::uuid4(),
            new User(Uuid::uuid4(), 'test@buddy.works', 'confirm-token', []),
            'Buddy',
            'buddy'
        ));
        $package->syncSuccess(
            $name,
            'Package description',
            $latestVersion,
            $unencounteredVersions,
            $unencounteredLinks,
            new \DateTimeImmutable()
        );

        return $package;
    }
}
