<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

abstract class IntegrationTestCase extends KernelTestCase
{
    protected FixturesManager $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->fixtures = new FixturesManager(self::$kernel->getContainer()->get('test.service_container'));
    }

    protected function container(): TestContainer
    {
        return self::$kernel->getContainer()->get('test.service_container');
    }

    /**
     * @param StampInterface[] $stamps
     */
    protected function dispatchMessage(object $message, array $stamps = []): void
    {
        $this->container()->get(MessageBusInterface::class)->dispatch($message, $stamps);
    }

    /**
     * For the rare test that needs a connection of its own, outside the one the test
     * transaction wraps. Resolved the way Symfony resolves %env(DATABASE_URL)%: the
     * superglobals first, then the process environment. Plain getenv() would miss it on
     * a developer machine, where config/bootstrap.php loads .env with usePutenv
     * disabled, while still working in CI where the workflow exports it for real.
     */
    protected static function databaseUrl(): string
    {
        /** @var string|false $url */
        $url = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL');

        if ($url === false || $url === '') {
            self::fail('DATABASE_URL is not set, cannot reach the database this test needs');
        }

        return $url;
    }

    protected function createRequestWithSession(): Request
    {
        $request = Request::createFromGlobals();
        $request->setSession($this->container()->get('session.factory')->createSession());
        $this->container()->get('request_stack')->push($request);

        return $request;
    }
}
