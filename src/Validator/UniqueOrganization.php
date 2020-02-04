<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueOrganization extends Constraint
{
    public string $message = 'Organization "{{ value }}" already exists.';
}
