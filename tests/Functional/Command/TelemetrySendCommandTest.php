<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\TelemetrySendCommand;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Tester\CommandTester;

final class TelemetrySendCommandTest extends FunctionalTestCase
{
    public function testTelemetrySendWithoutInstanceIdFile(): void
    {
        @unlink($this->instanceIdFile());

        $commandTester = new CommandTester(
            $this->container()->get(TelemetrySendCommand::class)
        );

        self::assertEquals(0, $commandTester->execute([]));
    }

    public function testTelemetrySendWithTelemetryDisabled(): void
    {
        $this->generateInstanceIdFile();

        $commandTester = new CommandTester(
            $this->container()->get(TelemetrySendCommand::class)
        );

        self::assertEquals(0, $commandTester->execute([]));
    }

    public function testTelemetrySend(): void
    {
        $this->generateInstanceIdFile();
        $this->fixtures->changeConfig('telemetry', 'enabled');

        $commandTester = new CommandTester(
            $this->container()->get(TelemetrySendCommand::class)
        );

        self::assertEquals(0, $commandTester->execute([]));
    }

    private function generateInstanceIdFile(): void
    {
        \file_put_contents($this->instanceIdFile(), Uuid::uuid4());
    }

    private function instanceIdFile(): string
    {
        return $this->container()->getParameter('instance_id_file');
    }
}
