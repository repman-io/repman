<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

use JsonSerializable;

abstract class Paginated implements JsonSerializable
{
    /**
     * @param object[] $data
     */
    public function __construct(protected array $data, protected int $total, protected Links $links)
    {
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
