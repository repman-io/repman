<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OauthToken;
use Buddy\Repman\Message\User\CreateOauthToken;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CreateOauthTokenHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(CreateOauthToken $message): void
    {
        /** @var User */
        $user = $this->em
            ->getRepository(User::class)
            ->find($message->userId());

        $token = new OauthToken(
            Uuid::fromString($message->id()),
            $user,
            $message->type(),
            $message->value()
        );
    }
}
