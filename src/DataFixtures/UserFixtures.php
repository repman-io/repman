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
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 20; ++$i) {
            $this->messageBus->dispatch(new CreateUser(
                Uuid::uuid4()->toString(),
                uniqid().'@buddy.works',
                'secret123',
                Uuid::uuid4()->toString(),
                ['ROLE_USER']
            ));
        }
    }
}
