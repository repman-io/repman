<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Query\Admin\OrganizationQuery;
use Doctrine\ORM\EntityManagerInterface;
use Munus\Control\Option;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CreateOrganizationHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private OrganizationQuery $orgQuery;

    public function __construct(EntityManagerInterface $em, OrganizationQuery $orgQuery)
    {
        $this->em = $em;
        $this->orgQuery = $orgQuery;
    }

    /**
     * @return Option<string>
     */
    public function __invoke(CreateOrganization $message): Option
    {
        /** @var User */
        $user = $this->em
            ->getRepository(User::class)
            ->find($message->ownerId());

        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User does not exist');
        }

        $organization = new Organization(
            Uuid::fromString($message->id()),
            $user,
            $message->name(),
        );

        $this->em->persist($organization);
        $this->em->flush();

        return Option::none();
    }
}
