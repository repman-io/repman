<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\SecurityChecker;

final class Advisory
{
    private string $title;
    private string $cve;
    private string $link;
    /**
     * @var Versions[]
     */
    private array $branches;

    /**
     * @param Versions[] $branches
     */
    public function __construct(string $title, string $cve, string $link, array $branches)
    {
        $this->title = $title;
        $this->cve = $cve;
        $this->link = $link;
        $this->branches = $branches;
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
