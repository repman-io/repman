<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CreateOrganizationHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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
            self::sanitizeString($message->name()),
        );

        $this->em->persist($organization);
        $this->em->flush();
    }

    private static function sanitizeString(string $text): string
    {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: '';
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', ' ', (string) $text);
        $text = preg_replace('~\s+~', '-', (string) $text);

        return trim((string) $text, '-');
    }
}
