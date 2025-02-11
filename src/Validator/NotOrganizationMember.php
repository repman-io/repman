<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Symfony\Component\Validator\Constraint;

final class NotOrganizationMember extends Constraint
{
    public string $alreadyInvitedMessage = 'User "{{ value }}" is already invited to this organization';

    public string $alreadyMemberMessage = 'User "{{ value }}" is already a member of this organization';

    public string $organizationId = '';

    public function getRequiredOptions()
    {
        return ['organizationId'];
    }
}
