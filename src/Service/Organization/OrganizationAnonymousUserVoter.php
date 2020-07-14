<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Security\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrganizationAnonymousUserVoter extends Voter
{
    private OrganizationQuery $organizations;

    public function __construct(OrganizationQuery $organizations)
    {
        $this->organizations = $organizations;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [
            'ROLE_ORGANIZATION_ANONYMOUS_USER',
        ], true);
    }

    /**
     * @param mixed   $subject
     * @param mixed[] $attributes
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            return self::ACCESS_ABSTAIN;
        }

        return parent::vote($token, $subject, $attributes);
    }

    /**
     * @param mixed|Request $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $organization = $subject instanceof Request
            ? $this->organizations->getByAlias($subject->get('organization'))->getOrNull()
            : $subject;

        if ($organization instanceof Organization) {
            return $organization->hasAnonymousAccess();
        }

        return false;
    }
}
