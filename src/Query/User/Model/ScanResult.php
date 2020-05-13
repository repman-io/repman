<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use Buddy\Repman\Entity\Organization\Package\ScanResult as ScanResultEntity;

final class ScanResult
{
    private \DateTimeImmutable $date;
    private string $status = ScanResultEntity::STATUS_PENDING;
    private string $version;

    /**
     * @var mixed[]
     */
    private array $content;

    public static function statusPending(): string
    {
        return ScanResultEntity::STATUS_PENDING;
    }

    /**
     * @param mixed[] $content
     */
    public function __construct(\DateTimeImmutable $date, string $status, string $version, array $content)
    {
        $this->date = $date;
        $this->status = $status;
        $this->version = $version;
        $this->content = $content;
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

    public function isError(): bool
    {
        return $this->status() === ScanResultEntity::STATUS_ERROR;
    }

    public function contentFormatted(): string
    {
        if ($this->isOk()) {
            return 'no advisories';
        }

        $result = [];
        foreach ($this->content as $dependency => $details) {
            $dependency = htmlspecialchars($dependency);

            if ($this->isError()) {
                $errorMsg = htmlspecialchars($details);
                $result[] = "<p><b>$dependency</b> - $errorMsg</p>";

                continue;
            }

            $advisories = [];
            foreach ($details['advisories'] as $advisor) {
                $advisories[] = '<li>'.htmlspecialchars($advisor['title']).'</li>';
            }

            $version = htmlspecialchars($details['version']);
            $result[] = "<p><b>$dependency</b> (v$version)<ul>".implode('', $advisories).'</ul>';
        }

        return implode('', $result);
    }
}
