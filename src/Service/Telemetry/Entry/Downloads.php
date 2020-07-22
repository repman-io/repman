<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

final class Downloads
{
    private int $public;
    private int $private;

    public function __construct(int $public, int $private)
    {
        $this->public = $public;
        $this->private = $private;
    }

    /**
     * @return array<string,int>
     */
    public function toArray(): array
    {
        return [
            'public' => $this->public,
            'private' => $this->private,
        ];
    }
}
