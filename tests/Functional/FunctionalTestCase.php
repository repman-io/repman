<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional;

use Buddy\Repman\Tests\Integration\FixturesManager;
use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class FunctionalTestCase extends WebTestCase
{
    use PHPMatcherAssertions;

    protected KernelBrowser $client;
    protected FixturesManager $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->fixtures = new FixturesManager(self::$kernel->getContainer()->get('test.service_container'));
    }

    public function contentFromStream(callable $request): string
    {
        ob_start();
        $request();
        $content = (string) ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * @param array<mixed> $parameters
     */
    protected function urlTo(string $path, array $parameters = []): string
    {
        return $this->container()->get('router')->generate($path, $parameters);
    }

    protected function lastResponseBody(): string
    {
        return (string) $this->client->getResponse()->getContent();
    }

    protected function createAndLoginAdmin(string $email = 'test@buddy.works', string $password = 'password', ?string $confirmToken = null): string
    {
        if (static::$booted) {
            self::ensureKernelShutdown();
        }
        $this->client = static::createClient([], [
            'PHP_AUTH_USER' => $email,
            'PHP_AUTH_PW' => $password,
        ]);
        $this->fixtures = new FixturesManager(self::$kernel->getContainer()->get('test.service_container'));

        return $this->fixtures->createAdmin($email, $password, $confirmToken);
    }

    protected function loginUser(string $email, string $password): void
    {
        if (static::$booted) {
            self::ensureKernelShutdown();
        }

        $this->client = static::createClient([], [
            'PHP_AUTH_USER' => $email,
            'PHP_AUTH_PW' => $password,
        ]);
    }

    protected function container(): ContainerInterface
    {
        return self::$kernel->getContainer()->get('test.service_container');
    }
}
