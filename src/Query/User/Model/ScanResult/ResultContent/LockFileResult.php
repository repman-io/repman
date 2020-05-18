<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\ScanResult\ResultContent;

final class LockFileResult
{
    private string $name;

    /**
     * @var Dependency[]
     */
    private array $dependencies;
    private bool $simple = false;

    /**
     * @param Dependency[] $dependencies
     */
    public function __construct(string $name, array $dependencies, bool $simple = false)
    {
        $this->name = $name;
        $this->dependencies = $dependencies;
        $this->simple = $simple;
    }

    public function html(): string
    {
        if ($this->dependencies === []) {
            return '';
        }

        $body = ["<div class='small text-muted'>{$this->name}</div>"];
        foreach ($this->dependencies as $dependency) {
            $advisories = $this->advisoriesHtml($dependency->advisories());
            $version = htmlspecialchars($dependency->version());
            $dependencyName = htmlspecialchars($dependency->name());
            $body[] = "<b>$dependencyName</b> (v$version)<ul>$advisories</ul>";
        }

        return implode('', $body);
    }

    /**
     * @param Advisory[] $advisories
     */
    private function advisoriesHtml(array $advisories): string
    {
        $details = [];
        foreach ($advisories as $advisor) {
            $parts = [];
            $parts[] = htmlspecialchars($advisor->title());

            if ($this->simple === false) {
                if ($advisor->cve() !== '') {
                    $parts[] = '<b>'.htmlspecialchars($advisor->cve()).'</b>';
                }

                if ($advisor->url() !== '') {
                    $url = htmlspecialchars($advisor->url());
                    $parts[] = "<a href='$url' target='_blank' rel='noopener noreferrer nofollow'>$url</a>";
                }
            }

            $advisorDetails = array_map(fn ($detail) => "$detail<br>", $parts);
            $details[] = '<li>'.implode('', $advisorDetails).'</li>';
        }

        return implode('', $details);
    }
}
