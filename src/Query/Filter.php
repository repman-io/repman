<?php

declare(strict_types=1);

namespace Buddy\Repman\Query;

use Symfony\Component\HttpFoundation\Request;

final class Filter
{
    private int $offset = 0;
    private int $limit = 20;
    private ?string $searchTerm = null;

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

    public static function fromRequest(Request $request): Filter
    {
        $limit = (int) $request->get('limit', 20);

        if ($limit <= 0) {
            $limit = 20;
        }

        $offset = (int) $request->get('offset', 0);

        if ($offset < 0) {
            $offset = 0;
        }

        return (new self())
            ->setOffset($offset)
            ->setLimit($limit)
            ->setSearchTerm($request->get('search', null));
    }
}
