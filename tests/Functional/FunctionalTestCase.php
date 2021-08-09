<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional;

use Buddy\Repman\Tests\Integration\FixturesManager;
use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    protected function urlTo(string $path, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->container()->get('router')->generate($path, $parameters, $referenceType);
    }

    protected function lastResponseBody(): string
    {
        return (string) $this->client->getResponse()->getContent();
    }

    protected function createAndLoginAdmin(string $email = 'test@buddy.works', string $password = 'password', ?string $confirmToken = null): string
    {
        $this->client->setServerParameter('PHP_AUTH_USER', $email);
        $this->client->setServerParameter('PHP_AUTH_PW', $password);

        return $this->fixtures->createAdmin($email, $password, $confirmToken);
    }

    protected function loginUser(string $email, string $password): void
    {
        $this->client->setServerParameter('PHP_AUTH_USER', $email);
        $this->client->setServerParameter('PHP_AUTH_PW', $password);
    }

    protected function logoutCurrentUser(): void
    {
        $this->client->setServerParameters([]);
    }

    protected function container(): ContainerInterface
    {
        return self::$kernel->getContainer()->get('test.service_container');
    }

    protected function loginApiUser(string $token): void
    {
        $this->client->setServerParameter('HTTP_X-API-TOKEN', $token);
    }
}
