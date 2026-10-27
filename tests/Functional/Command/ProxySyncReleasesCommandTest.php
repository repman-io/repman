<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ProxySyncReleasesCommand;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ProxySyncReleasesCommandTest extends FunctionalTestCase
{
    public function testIsANoOpThatTellsWhatToUseInstead(): void
    {
        $commandTester = new CommandTester($this->container()->get(ProxySyncReleasesCommand::class));

        self::assertSame(0, $commandTester->execute([]));
        self::assertStringContainsString('repman:proxy:sync-metadata', $commandTester->getDisplay());
    }
}
