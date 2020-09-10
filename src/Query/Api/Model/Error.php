<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

final class Error implements \JsonSerializable
{
    private string $field;

    private string $message;

    public function __construct(string $field, string $message)
    {
        $this->field = $field;
        $this->message = $message;
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
            'field' => $this->getField(),
            'message' => $this->getMessage(),
        ];
    }
}
