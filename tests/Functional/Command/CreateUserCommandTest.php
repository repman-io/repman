<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\CreateUserCommand;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateUserCommandTest extends FunctionalTestCase
{
    public function testCreateAdmin(): void
    {
        $commandTester = new CommandTester($this->container()->get(CreateUserCommand::class));
        $commandTester->execute([
            'email' => 'test@buddy.works',
            'password' => 'password',
        ]);

        self::assertStringContainsString('Created user with id:', $commandTester->getDisplay());
    }
}
