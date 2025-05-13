<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

use JsonSerializable;

final class Error implements JsonSerializable
{
    public function __construct(private readonly string $field, private readonly string $message)
    {
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'field' => $this->field,
            'message' => $this->message,
        ];
    }
}
