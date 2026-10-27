<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Query\User\OrganizationQuery;

/**
 * The host names Repman answers on, which are the names a certificate may be issued for: the app
 * (APP_HOST), the Packagist proxy (repo.APP_HOST) and one repository host per organization
 * (acme.repo.APP_HOST, with the configured separators). The organizations come from the database,
 * so the list cannot be written into a web server configuration.
 */
final class ServedHosts
{
    public function __construct(
        private OrganizationQuery $organizations,
        private string $domain,
        private string $domainSeparator,
        private string $organizationSeparator,
    ) {
    }

    public function serves(string $host): bool
    {
        $host = strtolower(rtrim($host, '.'));
        $domain = strtolower($this->domain);
        $repo = 'repo'.$this->domainSeparator.$domain;

        if ($host === $domain || $host === $repo) {
            return true;
        }

        $suffix = $this->organizationSeparator.$repo;
        if (!str_ends_with($host, $suffix)) {
            return false;
        }

        $alias = substr($host, 0, -strlen($suffix));

        return preg_match('/^[a-z0-9_-]+$/', $alias) === 1 && $this->organizations->getByAlias($alias)->isPresent();
    }
}
