<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class BytesExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_bytes', [$this, 'formatBytes']),
        ];
    }

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf("%.{$precision}f", $bytes / (1024 ** $factor)).' '.$size[$factor];
    }
}
