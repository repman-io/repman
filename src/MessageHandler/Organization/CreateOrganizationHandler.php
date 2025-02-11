<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Service\Organization\AliasGenerator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CreateOrganizationHandler implements MessageHandlerInterface
{
    public function __construct(private readonly UserRepository $users, private readonly OrganizationRepository $organizations, private readonly AliasGenerator $aliasGenerator)
    {
    }

    public function __invoke(CreateOrganization $message): void
    {
        $this->organizations->add(new Organization(
            Uuid::fromString($message->id()),
            $this->users->getById(Uuid::fromString($message->ownerId())),
            $message->name(),
            $this->aliasGenerator->generate($message->name())
        ));
    }
}
