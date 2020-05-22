<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\ScanResult\ResultContent;

final class Advisory
{
    private string $title;
    private string $cve;
    private string $url;

    public function __construct(string $title, string $cve, string $url)
    {
        $this->title = $title;
        $this->cve = $cve;
        $this->url = $url;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function cve(): string
    {
        return $this->cve;
    }

    public function url(): string
    {
        return $this->url;
    }
}
