<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Buddy\Repman\Service\Organization\AliasGenerator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AliasNotBlankValidator extends ConstraintValidator
{
    private AliasGenerator $aliasGenerator;

    public function __construct(AliasGenerator $aliasGenerator)
    {
        $this->aliasGenerator = $aliasGenerator;
    }

    /**
     * @param string|null   $value
     * @param AliasNotBlank $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (empty($this->aliasGenerator->generate($value))) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
