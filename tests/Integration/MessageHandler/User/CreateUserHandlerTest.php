<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\User;

use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Query\Admin\UserQuery\DbalUserQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreateUserHandlerTest extends IntegrationTestCase
{
    public function testCreateUser(): void
    {
        $this->container()->get(MessageBusInterface::class)->dispatch(new CreateUser(
            $id = '669dc378-f8a0-46c5-a515-38fddbd43165',
            'test@buddy.works',
            'secret123',
            'ccd235dc-fce0-4c48-a40f-999aeb63710d'
        ));

        $user = $this->container()->get(DbalUserQuery::class)->getByEmail('test@buddy.works');
        self::assertFalse($user->isEmpty());
    }
}
