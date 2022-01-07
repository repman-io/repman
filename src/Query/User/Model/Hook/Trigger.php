<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Hook;

final class Trigger
{
    private string $id;
    private string $type;

    public function __construct(
        string $id,
        string $type
    ) {
        $this->id = $id;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }
}
