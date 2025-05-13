<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use Buddy\Repman\Service\Proxy;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use RuntimeException;
use Throwable;

final class ProxyRegister
{
    /**
     * @var Set<string>
     */
    private readonly Set $urls;

    /**
     * @param string[] $urls
     */
    public function __construct(private readonly ProxyFactory $factory, array $urls = [])
    {
        $this->urls = Set::ofAll($urls);
    }

    /**
     * @return GenericList<Proxy>
     */
    public function all(): GenericList
    {
        $proxies = $this->urls->map(fn ($url) => $this->factory->create($url))->iterator()->toArray();
        $proxies[] = $this->factory->create('https://repo.packagist.org');

        return GenericList::ofAll($proxies);
    }

    /**
     * @throws Throwable
     */
    public function getByHost(string $host): Proxy
    {
        return $this->factory->create($this->urls
            ->add('https://repo.packagist.org')
            ->find(fn ($url) => (string) parse_url($url, PHP_URL_HOST) === $host)
            ->getOrElseThrow(new RuntimeException(sprintf('Proxy for %s not found', $host)))
        );
    }
}
