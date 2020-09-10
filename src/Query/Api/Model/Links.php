<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

final class Links implements \JsonSerializable
{
    private string $baseUrl;
    private int $page;
    private int $pages;

    public function __construct(string $baseUrl, int $page, int $pages)
    {
        $this->baseUrl = $baseUrl;
        $this->page = $page;
        $this->pages = $pages;
    }

    public function getFirst(): string
    {
        return $this->generateUrl(1);
    }

    public function getPrev(): ?string
    {
        return $this->page <= 1 ? null : $this->generateUrl($this->page - 1);
    }

    public function getNext(): ?string
    {
        return $this->page === $this->pages ? null : $this->generateUrl($this->page + 1);
    }

    public function getLast(): string
    {
        return $this->generateUrl($this->pages);
    }

    private function generateUrl(int $page): string
    {
        return $this->baseUrl."?page=$page";
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'first' => $this->getFirst(),
            'prev' => $this->getPrev(),
            'next' => $this->getNext(),
            'last' => $this->getLast(),
        ];
    }
}
