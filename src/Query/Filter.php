<?php

declare(strict_types=1);

namespace Buddy\Repman\Query;

use Symfony\Component\HttpFoundation\Request;

class Filter
{
    private int $offset = 0;
    private int $limit = 20;

    public function __construct(int $offset = 0, int $limit = 20)
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
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return array<string,string>
     */
    public function getQueryStringParams(): array
    {
        return [
            'offset' => (string) $this->getOffset(),
            'limit' => (string) $this->getLimit(),
        ];
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            (int) $request->get('offset', 0),
            (int) $request->get('limit', 20)
        );
    }
}
