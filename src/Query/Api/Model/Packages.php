<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

final class Packages extends Paginated
{
    /**
     * @var Package[]
     */
    protected array $data;
}
