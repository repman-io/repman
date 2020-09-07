<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

class Packages
{
    /**
     * @var Package[]
     */
    private array $data;
    private int $total;
    private Links $links;

    /**
     * @param Package[] $data
     */
    public function __construct(array $data, int $total, Links $links)
    {
        $this->data = $data;
        $this->total = $total;
        $this->links = $links;
    }

    /**
     * @return Package[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getLinks(): Links
    {
        return $this->links;
    }
}
