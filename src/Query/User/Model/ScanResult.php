<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use Buddy\Repman\Entity\Organization\Package\ScanResult as ScanResultEntity;
use Buddy\Repman\Query\User\Model\ScanResult\ResultContent;

final class ScanResult
{
    private \DateTimeImmutable $date;
    private string $status = ScanResultEntity::STATUS_PENDING;
    private string $version;
    private ResultContent $content;

    public static function statusPending(): string
    {
        return ScanResultEntity::STATUS_PENDING;
    }

    public function __construct(\DateTimeImmutable $date, string $status, string $version, string $content)
    {
        $this->date = $date;
        $this->status = $status;
        $this->version = $version;
        $this->content = new ResultContent($content);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function isOk(): bool
    {
        return $this->status() === ScanResultEntity::STATUS_OK;
    }

    public function isPending(): bool
    {
        return $this->status() === ScanResultEntity::STATUS_PENDING;
    }

    public function contentHtml(): string
    {
        if ($this->isOk()) {
            return 'no advisories';
        }

        return $this->content->html();
    }

    public function contentHtmlSimple(): string
    {
        if ($this->isOk()) {
            return 'no advisories';
        }

        return $this->content->htmlSimple();
    }
}
