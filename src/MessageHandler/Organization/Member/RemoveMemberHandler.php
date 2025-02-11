<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Member;

use Buddy\Repman\Message\Organization\Member\RemoveMember;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveMemberHandler implements MessageHandlerInterface
{
    public function __construct(private readonly OrganizationRepository $organizations, private readonly UserRepository $users)
    {
    }

    public function __invoke(RemoveMember $message): void
    {
        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->removeMember($this->users->getById(Uuid::fromString($message->userId())))
        ;
    }
}
