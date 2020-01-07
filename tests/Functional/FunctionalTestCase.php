<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalTestCase extends WebTestCase
{
    use PHPMatcherAssertions;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }
}
