<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

use JsonSerializable;

final class Errors implements JsonSerializable
{
    /**
     * @param Error[] $errors
     */
    public function __construct(private readonly array $errors)
    {
    }

    /**
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'errors' => $this->errors,
        ];
    }
}
