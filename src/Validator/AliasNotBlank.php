<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class AliasNotBlank extends Constraint
{
    public string $message = 'Name cannot consist of special characters only.';
}
