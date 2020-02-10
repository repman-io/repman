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
        return $attribute === 'ROLE_ORGANIZATION_MEMBER';
    }

    /**
     * @param Request $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->organizationQuery
            ->getByAlias($subject->get('organization'))
            ->map(function (Organization $organization) use ($user, $subject) {
                $subject->attributes->set('organization', $organization);

                return $organization->isOwnedBy($user->id()->toString());
            })
            ->getOrElse(false);
    }
}
