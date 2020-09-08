<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

use Buddy\Repman\Query\Admin\Model\User;
use Buddy\Repman\Query\Filter;
use Munus\Control\Option;

interface UserQuery
{
    /**
     * @return User[]
     */
    public function findAll(Filter $filter): array;

    /**
     * @return Option<User>
     */
    public function getByEmail(string $email): Option;

    /**
     * @return Option<User>
     */
    public function getById(string $id): Option;

    public function count(): int;
}
