<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

interface ConfigQuery
{
    /**
     * @return array<string,string>
     */
    public function findAll(): array;
}
