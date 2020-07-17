<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\CreateAdminCommand;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateAdminCommandTest extends FunctionalTestCase
{
    public function testCreateAdmin(): void
    {
        $command = $this->container()->get(CreateAdminCommand::class);
        $command->setApplication(new Application());
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'email' => 'test@buddy.works',
            'password' => 'password',
        ]);

        self::assertStringContainsString('Created admin user with id:', $commandTester->getDisplay());
    }
}
