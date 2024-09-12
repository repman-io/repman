<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\ChangeName;
use Buddy\Repman\Message\Organization\ChangeOAuthOwner;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ChangeOAuthOwnerHandler implements MessageHandlerInterface
{
    private UserRepository $userRepository;
    private OrganizationRepository $repositories;

    public function __construct(
        OrganizationRepository $repositories,
        UserRepository $userRepository
    )
    {
        $this->repositories = $repositories;
        $this->userRepository = $userRepository;
    }

    public function __invoke(ChangeOAuthOwner $message): void
    {
        if(empty($message->userId())) {
            $this->repositories
                ->getById(Uuid::fromString($message->organizationId()))
                ->changeOauthOwner(null)
            ;

            return;
        }

        $user = $this->userRepository->getById(Uuid::fromString($message->userId()));

        $this->repositories
            ->getById(Uuid::fromString($message->organizationId()))
            ->changeOauthOwner($user)
        ;
    }
}
