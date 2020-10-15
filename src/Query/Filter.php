<?php

declare(strict_types=1);

namespace Buddy\Repman\Query;

use Symfony\Component\HttpFoundation\Request;

class Filter
{
    private int $offset = 0;
    private int $limit = 20;
    private ?string $sortColumn = null;
    private string $sortOrder;

    public function __construct(int $offset = 0, int $limit = 20, ?string $sort = null)
    {
        if ($offset >= 0) {
            $this->offset = $offset;
        }

        if ($limit >= 0) {
            $this->limit = $limit;
        }

        if ($this->limit > 100) {
            $this->limit = 100;
        }

        // $sort = 'column:order'
        $sortParts = explode(':', $sort ?? '');
        if ($sortParts[0] !== '') {
            $this->sortColumn = $sortParts[0];
        }

        $sortOrder = $sortParts[1] ?? 'asc';
        $this->sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'asc';
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getSortColumn(): ?string
    {
        return $this->sortColumn;
    }

    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }

    public function hasSort(): bool
    {
        return $this->sortColumn !== null;
    }

    /**
     * @return array<string,string>
     */
    public function getQueryStringParams(): array
    {
        $return = [
            'offset' => (string) $this->getOffset(),
            'limit' => (string) $this->getLimit(),
        ];

        if ($this->hasSort()) {
            $return['sort'] = $this->sortColumn.':'.$this->sortOrder;
        }

        return $return;
    }

    public static function fromRequest(Request $request, ?string $defaultSortColumn = null): self
    {
        return new self(
            (int) $request->get('offset', 0),
            (int) $request->get('limit', 20),
            $request->get('sort', $defaultSortColumn),
        );
    }
}
