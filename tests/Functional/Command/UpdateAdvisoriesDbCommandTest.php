<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\UpdateAdvisoriesDbCommand;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class UpdateAdvisoriesDbCommandTest extends FunctionalTestCase
{
    public function testUpdate(): void
    {
        $commandTester = new CommandTester(
            $this->container()->get(UpdateAdvisoriesDbCommand::class)
        );
        $result = $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertEquals($result, 0);
        self::assertStringContainsString('Database successfully updated', $output);
    }
}
