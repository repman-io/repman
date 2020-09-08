<?php

declare(strict_types=1);

namespace Buddy\Repman\Query;

use Symfony\Component\HttpFoundation\Request;

final class Filter
{
    private int $offset = 0;
    private int $limit = 20;
    private ?string $searchTerm;

    public function __construct(int $offset = 0, int $limit = 20, ?string $searchTerm = null)
    {
        if ($offset > 0) {
            $this->offset = $offset;
        }

        if ($limit >= 0) {
            $this->limit = $limit;
        }

        $this->searchTerm = $searchTerm;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): Filter
    {
        $this->offset = $offset;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): Filter
    {
        $this->limit = $limit;

        return $this;
    }

    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(?string $searchTerm): Filter
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }

    public function hasSearchTerm(): bool
    {
        return $this->searchTerm !== null;
    }

    public static function fromRequest(Request $request): Filter
    {
        return new self(
            (int) $request->get('offset', 0),
            (int) $request->get('limit', 20),
            $request->get('search', null)
        );
    }
}
