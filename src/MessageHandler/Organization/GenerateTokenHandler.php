<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization\Token;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class GenerateTokenHandler implements MessageHandlerInterface
{
    public function __construct(private readonly OrganizationRepository $organizations, private readonly TokenGenerator $tokenGenerator)
    {
    }

    public function __invoke(GenerateToken $message): void
    {
        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->addToken(new Token(
                $this->tokenGenerator->generate(),
                $message->name()
            ))
        ;
    }
}
