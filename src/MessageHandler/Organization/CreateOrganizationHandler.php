<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Service\Organization\AliasGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CreateOrganizationHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private AliasGenerator $aliasGenerator;

    public function __construct(EntityManagerInterface $em, AliasGenerator $alias)
    {
        $this->em = $em;
        $this->aliasGenerator = $alias;
    }

    public function __invoke(CreateOrganization $message): void
    {
        /** @var User */
        $user = $this->em
            ->getRepository(User::class)
            ->find($message->ownerId());

        if (!$user instanceof User) {
            return;
        }

        $organization = new Organization(
            Uuid::fromString($message->id()),
            $user,
            $message->name(),
            $this->aliasGenerator->generate($message->name()),
        );

        $this->em->persist($organization);
        $this->em->flush();
    }
}
