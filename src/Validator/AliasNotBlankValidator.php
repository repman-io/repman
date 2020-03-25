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
     * @param mixed                    $value
     * @param Constraint|AliasNotBlank $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value || !$constraint instanceof AliasNotBlank) {
            return;
        }

        if ($this->aliasGenerator->generate($value) === '') {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
