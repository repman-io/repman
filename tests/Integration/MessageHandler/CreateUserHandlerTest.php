<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\CreateUser;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreateUserHandlerTest extends IntegrationTestCase
{
    public function testCreateUser(): void
    {
        $this->container()->get(MessageBusInterface::class)->dispatch(new CreateUser(
            '669dc378-f8a0-46c5-a515-38fddbd43165',
            'test@buddy.works',
            'secret123'
        ));

        $user = $this->container()->get(UserRepository::class)->find(Uuid::fromString('669dc378-f8a0-46c5-a515-38fddbd43165'));
        self::assertInstanceOf(User::class, $user);
    }
}
