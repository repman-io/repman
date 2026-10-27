<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueOrganization extends Constraint
{
    public string $message = 'Organization "{{ value }}" already exists.';
}
