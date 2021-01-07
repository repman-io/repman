<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Dist;

final class FilePatternFilterIterator extends \FilterIterator
{
    private ?string $excludedPattern = null;
    private string $inclusionPattern;

    /**
     * @param \Iterator<array<string,string>> $iterator    The Iterator to filter
     * @param string[]                        $directories An array of directories to exclude
     */
    public function __construct(\Iterator $iterator, array $directories, string $inclusionPattern)
    {
        $this->inclusionPattern = $inclusionPattern;
        $patterns = [];
        foreach ($directories as $directory) {
            $directory = \rtrim($directory, '/');
            $patterns[] = \preg_quote($directory, '#');
        }
        if (\count($patterns) > 0) {
            $this->excludedPattern = '#(?:^|/)(?:'.\implode('|', $patterns).')(?:/|$)#';
        }

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool True if the value should be kept, false otherwise
     */
    public function accept(): bool
    {
        /** @var array<string, string> $item */
        $item = parent::current();
        $path = $item['path'];

        if ($this->excludedPattern !== null) {
            $normalizedPath = \str_replace('\\', '/', $path);

            if ($this->pathMatchesExclusionPattern($normalizedPath)) {
                return false;
            }
        }

        return 1 === \preg_match($this->inclusionPattern, $path);
    }

    private function pathMatchesExclusionPattern(string $path): bool
    {
        return $this->excludedPattern !== null && \preg_match($this->excludedPattern, $path) === 1;
    }
}
