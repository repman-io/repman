<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Member;

use Buddy\Repman\Message\Organization\Member\AcceptInvitation;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AcceptInvitationHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;
    private UserRepository $users;

    public function __construct(OrganizationRepository $organizations, UserRepository $users)
    {
        $this->organizations = $organizations;
        $this->users = $users;
    }

    public function __invoke(AcceptInvitation $message): void
    {
        $this->organizations
            ->getByInvitationToken($message->token())
            ->acceptInvitation($message->token(), $this->users->getById(Uuid::fromString($message->userId())));
    }
}
