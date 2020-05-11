<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Query\Admin\ConfigQuery;
use Symfony\Contracts\Cache\CacheInterface;

final class Config
{
    const CACHE_KEY = 'values';

    private ConfigQuery $configQuery;
    private CacheInterface $cache;

    /**
     * @var array<string,string>
     */
    private array $values = [];

    public function __construct(ConfigQuery $configQuery, CacheInterface $configCache)
    {
        $this->configQuery = $configQuery;
        $this->cache = $configCache;
    }

    public function get(string $key): ?string
    {
        $this->load();

        return $this->getAll()[$key] ?? null;
    }

    public function userRegistrationEnabled(): bool
    {
        return $this->get('user_registration') === 'enabled';
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
        $this->values = $this->cache->get(
            self::CACHE_KEY,
            fn () => $this->configQuery->findAll()
        );
    }
}
