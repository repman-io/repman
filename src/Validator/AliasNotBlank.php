<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AliasNotBlank extends Constraint
{
    public string $message = 'Name cannot consist of special characters only.';
}
