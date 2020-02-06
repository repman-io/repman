<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Package;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Package\CreatePackage;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CreatePackageHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(CreatePackage $message): void
    {
        /** @var Organization */
        $organization = $this->em
            ->getRepository(Organization::class)
            ->find($message->organizationId());

        if (!$organization instanceof Organization) {
            throw new \InvalidArgumentException('Organization does not exist');
        }

        $package = new Package(
            Uuid::fromString($message->id()),
            $organization,
            $message->url(),
            'name',
            'description',
            'version number'
        );

        $this->em->persist($package);
        $this->em->flush();
    }
}
