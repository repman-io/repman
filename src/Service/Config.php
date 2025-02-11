<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Query\Admin\ConfigQuery;
use Symfony\Contracts\Cache\CacheInterface;

final class Config
{
    public const CACHE_KEY = 'values';

    public const TELEMETRY = 'telemetry';

    public const TELEMETRY_ENABLED = 'enabled';

    public const TELEMETRY_DISABLED = 'disabled';

    public const TECHNICAL_EMAIL = 'technical_email';

    /**
     * @var array<string,string>
     */
    private array $values = [];

    public function __construct(private readonly ConfigQuery $configQuery, private readonly CacheInterface $cache)
    {
    }

    public function get(string $key): ?string
    {
        $this->load();

        return $this->getAll()[$key] ?? null;
    }

    public function localLoginEnabled(): bool
    {
        return $this->get('local_authentication') !== 'disabled';
    }

    public function localRegistrationEnabled(): bool
    {
        return $this->get('local_authentication') === 'login_and_registration';
    }

    public function oauthRegistrationEnabled(): bool
    {
        return $this->get('oauth_registration') === 'enabled';
    }

    public function userRegistrationEnabled(): bool
    {
        if ($this->localRegistrationEnabled()) {
            return true;
        }

        return $this->oauthRegistrationEnabled();
    }

    public function telemetryEnabled(): bool
    {
        return $this->get(self::TELEMETRY) === self::TELEMETRY_ENABLED;
    }

    public function isTechnicalEmailSet(): bool
    {
        return trim((string) $this->get(self::TECHNICAL_EMAIL)) !== '';
    }

    /**
     * @return array<string,string>
     */
    public function getAll(): array
    {
        $this->load();

        return $this->values;
    }

    public function invalidate(): void
    {
        $this->cache->delete(self::CACHE_KEY);
    }

    private function load(): void
    {
        if ($this->values === []) {
            $this->values = $this->cache->get(
                self::CACHE_KEY,
                fn () => $this->configQuery->findAll()
            );
        }
    }
}
