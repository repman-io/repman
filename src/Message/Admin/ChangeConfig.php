<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Admin;

final class ChangeConfig
{
    /**
     * @var array<string,string>
     */
    private array $values;

    /**
     * @param array<string,string> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @return array<string,string|null>
     */
    public function values(): array
    {
        return $this->values;
    }
}
