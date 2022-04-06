<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Proxy;

use RuntimeException;
use Buddy\Repman\Service\Proxy;
use Munus\Collection\GenericList;
use Munus\Collection\Set;

final class ProxyRegister
{
    private ProxyFactory $factory;

    /**
     * @var Set<string>
     */
    private Set $urls;

    /**
     * @param string[] $urls
     */
    public function __construct(ProxyFactory $factory, array $urls = [])
    {
        $this->factory = $factory;
        $this->urls = Set::ofAll($urls);
    }

    /**
     * @return GenericList<Proxy>
     */
    public function all(): GenericList
    {
        $proxies = $this->urls->map(fn ($url) => $this->factory->create($url))->iterator()->toArray();
        $proxies[] = $this->factory->create('https://packagist.org');

        return GenericList::ofAll($proxies);
    }

    public function getByHost(string $host): Proxy
    {
        return $this->factory->create($this->urls
            ->add('https://packagist.org')
            ->find(fn ($url) => (string) parse_url($url, PHP_URL_HOST) === $host)
            ->getOrElseThrow(new RuntimeException(sprintf('Proxy for %s not found', $host)))
        );
    }
}
