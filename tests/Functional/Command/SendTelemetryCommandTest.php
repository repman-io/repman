<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\SendTelemetryCommand;
use Buddy\Repman\Service\Config;
use Buddy\Repman\Service\Telemetry\TelemetryEndpoint;
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
        self::assertTrue($this->container()->get(TelemetryEndpoint::class)->entryWasNotSent());
    }

    public function testSendTelemetryWithTelemetryDisabled(): void
    {
        $instanceId = $this->generateInstanceIdFile();
        $this->fixtures->changeConfig(Config::TELEMETRY, Config::TELEMETRY_DISABLED);

        $commandTester = new CommandTester(
            $this->container()->get(SendTelemetryCommand::class)
        );

        self::assertEquals(0, $commandTester->execute([]));
        self::assertTrue($this->container()->get(TelemetryEndpoint::class)->entryWasNotSent());
    }

    public function testSendTelemetry(): void
    {
        $this->fixtures->createPackage(Uuid::uuid4()->toString());

        $instanceId = $this->generateInstanceIdFile();
        $this->fixtures->changeConfig(Config::TELEMETRY, Config::TELEMETRY_ENABLED);

        $commandTester = new CommandTester(
            $this->container()->get(SendTelemetryCommand::class)
        );

        self::assertEquals(0, $commandTester->execute([]));
        self::assertTrue($this->container()->get(TelemetryEndpoint::class)->wasEntrySent($instanceId));
    }

    private function generateInstanceIdFile(): string
    {
        $instanceId = Uuid::uuid4()->toString();
        \file_put_contents($this->instanceIdFile(), $instanceId);

        return $instanceId;
    }

    private function instanceIdFile(): string
    {
        return (string) $this->container()->getParameter('instance_id_file'); // @phpstan-ignore-line
    }
}
