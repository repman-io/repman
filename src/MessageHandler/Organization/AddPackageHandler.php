<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\User\OauthToken;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AddPackageHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;
    private EntityManagerInterface $em;

    public function __construct(OrganizationRepository $organizations, EntityManagerInterface $em)
    {
        $this->organizations = $organizations;
        $this->em = $em;
    }

    public function __invoke(AddPackage $message): void
    {
        $oauthToken = null;
        if ($message->oauthTokenId()) {
            $oauthToken = $this->em
                ->getRepository(OauthToken::class)
                ->find($message->oauthTokenId());
        }

        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->addPackage(
                new Package(
                    Uuid::fromString($message->id()),
                    $message->type(),
                    $message->url(),
                    $oauthToken
                )
            )
        ;
    }
}
