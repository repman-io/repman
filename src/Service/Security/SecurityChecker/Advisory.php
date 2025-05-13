<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\SecurityChecker;

final class Advisory
{
    /**
     * @param Versions[] $branches
     */
    public function __construct(private readonly string $title, private readonly string $cve, private readonly string $link, private readonly array $branches)
    {
    }

    /**
     * @return Versions[]
     */
    public function branches(): array
    {
        return $this->branches;
    }

    /**
     * @return array<string,string>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'cve' => $this->cve,
            'link' => $this->link,
        ];
    }
}
