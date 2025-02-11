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
    public function __construct(private readonly OrganizationRepository $organizations, private readonly TokenGenerator $tokenGenerator)
    {
    }

    public function __invoke(RegenerateToken $message): void
    {
        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->regenerateToken($message->token(), $this->tokenGenerator->generate())
        ;
    }
}
