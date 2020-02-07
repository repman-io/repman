<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\RegenerateToken;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RegenerateTokenHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;
    private TokenGenerator $tokenGenerator;

    public function __construct(OrganizationRepository $organizations, TokenGenerator $tokenGenerator)
    {
        $this->organizations = $organizations;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function __invoke(RegenerateToken $message): void
    {
        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->regenerateToken($message->token(), $this->tokenGenerator->generate())
        ;
    }
}
