<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Buddy\Repman\Query\User\OrganizationQuery;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class NotOrganizationMemberValidator extends ConstraintValidator
{
    public function __construct(private readonly OrganizationQuery $organizationQuery)
    {
    }

    /**
     * @param mixed                            $value
     * @param Constraint|NotOrganizationMember $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value || !$constraint instanceof NotOrganizationMember) {
            return;
        }

        if ($this->organizationQuery->isInvited($constraint->organizationId, $value)) {
            $this->context->buildViolation($constraint->alreadyInvitedMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }

        if ($this->organizationQuery->isMember($constraint->organizationId, $value)) {
            $this->context->buildViolation($constraint->alreadyMemberMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
