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
    private UserRepository $users;
    private OrganizationRepository $organizations;
    private AliasGenerator $aliasGenerator;

    public function __construct(UserRepository $users, OrganizationRepository $organizations, AliasGenerator $aliasGenerator)
    {
        $this->users = $users;
        $this->organizations = $organizations;
        $this->aliasGenerator = $aliasGenerator;
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
