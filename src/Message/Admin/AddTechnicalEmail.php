<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Admin;

final class AddTechnicalEmail
{
    private string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function email(): string
    {
        return $this->email;
    }
}
