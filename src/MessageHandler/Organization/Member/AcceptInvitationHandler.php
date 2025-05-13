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
    public function __construct(private readonly OrganizationRepository $organizations, private readonly UserRepository $users)
    {
    }

    public function __invoke(AcceptInvitation $message): void
    {
        $this->organizations
            ->getByInvitationToken($message->token())
            ->acceptInvitation($message->token(), $this->users->getById(Uuid::fromString($message->userId())));
    }
}
