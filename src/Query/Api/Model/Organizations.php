<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

final class Organizations extends Paginated
{
    /**
     * @var Organization[]
     */
    protected array $data;
}
