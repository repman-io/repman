<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Security\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrganizationVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [
            'ROLE_ORGANIZATION_MEMBER',
            'ROLE_ORGANIZATION_OWNER',
        ], true);
    }

    /**
     * @param mixed|Request $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($subject instanceof Organization) {
            return $attribute === 'ROLE_ORGANIZATION_OWNER' ? $subject->isOwner($user->id()) : $subject->isMember($user->id());
        }

        if ($subject instanceof Request) {
            foreach ($user->organizations() as $organization) {
                if ($organization->alias() !== $subject->get('organization')) {
                    continue;
                }

                if ($attribute === 'ROLE_ORGANIZATION_OWNER') {
                    return $organization->role() === 'owner';
                }

                return true;
            }
        }

        return false;
    }
}
