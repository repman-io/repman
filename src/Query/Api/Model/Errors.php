<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

final class Errors implements \JsonSerializable
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

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'errors' => $this->getErrors(),
        ];
    }
}
