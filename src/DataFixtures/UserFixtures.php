<?php

declare(strict_types=1);

namespace Buddy\Repman\DataFixtures;

use Buddy\Repman\Message\User\CreateUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;

final class UserFixtures extends Fixture
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 20; ++$i) {
            $this->messageBus->dispatch(new CreateUser(
                Uuid::uuid4()->toString(),
                uniqid().'@buddy.works',
                'secret123',
                ['ROLE_USER', 'ROLE_ADMIN']
            ));
        }
    }
}
