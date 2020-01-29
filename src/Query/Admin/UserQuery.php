<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

use Buddy\Repman\Query\Admin\Model\User;
use Munus\Control\Option;

interface UserQuery
{
    /**
     * @return User[]
     */
    public function findAll(int $limit = 20, int $offset = 0): array;

    /**
     * @return Option<User>
     */
    public function getById(string $id): Option;

    public function count(): int;
}
