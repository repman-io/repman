<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Buddy\Repman\Query\Admin\UserQuery;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueEmailValidator extends ConstraintValidator
{
    private UserQuery $usersQuery;

    public function __construct(UserQuery $usersQuery)
    {
        $this->usersQuery = $usersQuery;
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

        $value = \mb_strtolower($value);

        if (!$this->usersQuery->getByEmail($value)->isEmpty()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
