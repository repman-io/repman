<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization\Token;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateTokenHandler
{
    private OrganizationRepository $organizations;
    private TokenGenerator $tokenGenerator;

    public function __construct(OrganizationRepository $organizations, TokenGenerator $tokenGenerator)
    {
        $this->organizations = $organizations;
        $this->tokenGenerator = $tokenGenerator;
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
