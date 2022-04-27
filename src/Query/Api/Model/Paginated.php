<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

abstract class Paginated implements \JsonSerializable
{
    /**
     * @var object[]
     */
    protected array $data;
    protected int $total;
    protected Links $links;

    /**
     * @param object[] $data
     */
    public function __construct(array $data, int $total, Links $links)
    {
        $this->data = $data;
        $this->total = $total;
        $this->links = $links;
    }

    /**
     * @return object[]
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

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->getData(),
            'total' => $this->getTotal(),
            'links' => $this->getLinks(),
        ];
    }
}
