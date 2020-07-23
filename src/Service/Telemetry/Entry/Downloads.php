<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

final class Downloads
{
    private int $proxy;
    private int $private;

    public function __construct(int $proxy, int $private)
    {
        $this->proxy = $proxy;
        $this->private = $private;
    }

    /**
     * @return array<string,int>
     */
    public function toArray(): array
    {
        return [
            'proxy' => $this->proxy,
            'private' => $this->private,
        ];
    }
}
