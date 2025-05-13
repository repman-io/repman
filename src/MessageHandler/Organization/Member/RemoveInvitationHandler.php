<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Member;

use Buddy\Repman\Message\Organization\Member\RemoveInvitation;
use Buddy\Repman\Repository\OrganizationRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveInvitationHandler implements MessageHandlerInterface
{
    public function __construct(private readonly OrganizationRepository $organizations)
    {
    }

    public function __invoke(RemoveInvitation $message): void
    {
        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->removeInvitation($message->token())
        ;
    }
}
