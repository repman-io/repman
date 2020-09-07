<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

class Organizations
{
    /**
     * @var Organization[]
     */
    private array $data;
    private int $total;
    private Links $links;

    /**
     * @param Organization[] $data
     */
    public function __construct(array $data, int $total, Links $links)
    {
        $this->data = $data;
        $this->total = $total;
        $this->links = $links;
    }

    /**
     * @return Organization[]
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
