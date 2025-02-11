<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Buddy\Repman\Query\Admin\UserQuery;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function mb_strtolower;

class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(private readonly UserQuery $usersQuery)
    {
    }

    /**
     * @param mixed                  $value
     * @param Constraint|UniqueEmail $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value || !$constraint instanceof UniqueEmail) {
            return;
        }

        $value = mb_strtolower((string) $value);

        if (!$this->usersQuery->getByEmail($value)->isEmpty()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
