<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Security\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrganizationVoter extends Voter
{
    private OrganizationQuery $organizations;

    public function __construct(OrganizationQuery $organizations)
    {
        $this->organizations = $organizations;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [
            'ROLE_ORGANIZATION_MEMBER',
            'ROLE_ORGANIZATION_OWNER',
            'ROLE_ORGANIZATION_ANONYMOUS_USER',
        ], true);
    }

    /**
     * @param mixed   $subject
     * @param mixed[] $attributes
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        if (!$token->getUser() instanceof User) {
            return self::ACCESS_ABSTAIN;
        }

        return parent::vote($token, $subject, $attributes);
    }

    /**
     * @param mixed|Request $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /**
         * @var User
         */
        $user = $token->getUser();

        if ($subject instanceof Organization) {
            return $attribute === 'ROLE_ORGANIZATION_OWNER' ? $subject->isOwner($user->id()) : $subject->isMember($user->id());
        }

        if ($subject instanceof Request) {
            $alias = $subject->get('organization');
            $checkOrganization = $this->organizations->getByAlias($alias)->getOrNull();
            if ($checkOrganization instanceof Organization) {
                $subject->attributes->set('organization', $checkOrganization);
            }

            if ($checkOrganization instanceof Organization && $checkOrganization->hasAnonymousAccess()) {
                return true;
            }

            foreach ($user->organizations() as $organization) {
                if ($organization->alias() !== $alias) {
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
