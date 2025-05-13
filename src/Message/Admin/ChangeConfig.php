<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Admin;

final class ChangeConfig
{
    /**
     * @param array<string,string> $values
     */
    public function __construct(private readonly array $values)
    {
    }

    /**
     * @return array<string,string|null>
     */
    public function values(): array
    {
        return $this->values;
    }
}
