<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\SendTelemetryCommand;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Tester\CommandTester;

final class SendTelemetryCommandTest extends FunctionalTestCase
{
    public function testSendTelemetryWithoutInstanceIdFile(): void
    {
        @unlink($this->instanceIdFile());

        $commandTester = new CommandTester(
            $this->container()->get(SendTelemetryCommand::class)
        );

        self::assertEquals(0, $commandTester->execute([]));
    }

    public function testSendTelemetryWithTelemetryDisabled(): void
    {
        $this->generateInstanceIdFile();
        $this->fixtures->changeConfig('telemetry', 'disabled');

        $commandTester = new CommandTester(
            $this->container()->get(SendTelemetryCommand::class)
        );

        self::assertEquals(0, $commandTester->execute([]));
    }

    public function testSendTelemetry(): void
    {
        $this->fixtures->createPackage(Uuid::uuid4()->toString());

        $this->generateInstanceIdFile();
        $this->fixtures->changeConfig('telemetry', 'enabled');

        $commandTester = new CommandTester(
            $this->container()->get(SendTelemetryCommand::class)
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
