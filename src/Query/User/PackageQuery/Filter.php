<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\PackageQuery;

use Symfony\Component\HttpFoundation\Request;

class Filter extends \Buddy\Repman\Query\Filter
{
    private ?string $searchTerm;

    public function __construct(int $offset = 0, int $limit = 20, ?string $sort = null, ?string $searchTerm = null)
    {
        parent::__construct($offset, $limit, $sort);

        $this->searchTerm = $searchTerm;
    }

    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function hasSearchTerm(): bool
    {
        return $this->searchTerm !== null;
    }

    public function hasLinkSearch(): bool
    {
        // @todo Allow more search types?
        return $this->searchTerm !== null && str_starts_with($this->searchTerm, 'depends:');
    }

    public function getLinkSearch(): ?string
    {
        if ($this->searchTerm === null) {
            return null;
        }

        return substr($this->searchTerm, strlen('depends:'));
    }

    public function getQueryStringParams(): array
    {
        $params = parent::getQueryStringParams();

        if ($this->hasSearchTerm()) {
            $params['search'] = (string) $this->getSearchTerm();
        }

        return $params;
    }

    public static function fromRequest(Request $request, ?string $defaultSortColumn = null): self
    {
        return new self(
            (int) $request->query->get('offset', 0),
            (int) $request->query->get('limit', 20),
            $request->query->get('sort', $defaultSortColumn),
            // the search form always submits the field; an empty one is no search
            ($request->query->get('search') ?? '') !== '' ? $request->query->get('search') : null,
        );
    }
}
