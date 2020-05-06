<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrganizationVoter extends Voter
{
    private OrganizationQuery $organizationQuery;

    public function __construct(OrganizationQuery $organizationQuery)
    {
        $this->organizationQuery = $organizationQuery;
    }

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
        $userId = $user->id()->toString();

        if ($subject instanceof Organization) {
            return $attribute === 'ROLE_ORGANIZATION_OWNER' ? $subject->isOwner($userId) : $subject->isMember($userId);
        }

        if ($subject instanceof Request) {
            return $this->organizationQuery
                ->getByAlias($subject->get('organization'))
                ->map(function (Organization $organization) use ($userId, $subject, $attribute): bool {
                    $subject->attributes->set('organization', $organization);

                    if ($attribute === 'ROLE_ORGANIZATION_OWNER') {
                        return $organization->isOwner($userId);
                    }

                    return $organization->isMember($userId);
                })
                ->getOrElse(false);
        }

        return false;
    }
}
