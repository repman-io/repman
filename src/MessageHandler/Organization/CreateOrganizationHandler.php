<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Query\Admin\OrganizationQuery;
use Buddy\Repman\Service\Organization\AliasGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Munus\Control\Option;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CreateOrganizationHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private OrganizationQuery $orgQuery;
    private AliasGenerator $aliasGenerator;

    public function __construct(EntityManagerInterface $em, OrganizationQuery $orgQuery, AliasGenerator $alias)
    {
        $this->em = $em;
        $this->orgQuery = $orgQuery;
        $this->aliasGenerator = $alias;
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
            return Option::some('User does not exist');
        }

        $alias = $this->aliasGenerator->generate($message->name());
        $orgOption = $this->orgQuery->getByAlias($alias);

        if (!$orgOption->isEmpty()) {
            return Option::some('Organization name already exist');
        }

        $organization = new Organization(
            Uuid::fromString($message->id()),
            $user,
            $message->name(),
            $alias,
        );

        $this->em->persist($organization);
        $this->em->flush();

        return Option::none();
    }
}
