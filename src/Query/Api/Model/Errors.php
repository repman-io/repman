<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

class Errors
{
    /**
     * @var Error[]
     */
    private array $errors;

    /**
     * @param Error[] $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
