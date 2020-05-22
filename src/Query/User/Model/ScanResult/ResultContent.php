<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\ScanResult;

use Buddy\Repman\Query\User\Model\ScanResult\ResultContent\Advisory;
use Buddy\Repman\Query\User\Model\ScanResult\ResultContent\Dependency;
use Buddy\Repman\Query\User\Model\ScanResult\ResultContent\LockFileResult;

final class ResultContent
{
    private string $json;

    public function __construct(string $json)
    {
        $this->json = $json;
    }

    public function html(): string
    {
        return $this->formatContent();
    }

    public function htmlSimple(): string
    {
        return $this->formatContent(true);
    }

    private function formatContent(bool $simple = false): string
    {
        $result = [];
        foreach (json_decode($this->json, true) as $lockFile => $lockFileResult) {
            if ($lockFile === 'exception') {
                foreach ($lockFileResult as $class => $message) {
                    $message = htmlspecialchars($message);
                    $result[] = "<b>$class</b> - $message";
                }

                return implode(', ', $result);
            }

            $dependencies = [];
            foreach ($lockFileResult as $dependency => $details) {
                $advisories = array_map(fn ($advisor) => new Advisory(
                    $advisor['title'],
                    $advisor['cve'],
                    $advisor['link']
                ), $details['advisories']);

                $dependencies[] = new Dependency($dependency, $details['version'], $advisories);
            }

            $result[] = (new LockFileResult($lockFile, $dependencies, $simple))->html();
        }

        return implode('', $result);
    }
}
