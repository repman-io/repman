<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueEmail extends Constraint
{
    public string $message = 'Email "{{ value }}" already exists.';
}
