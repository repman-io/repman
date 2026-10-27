<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class TlsControllerTest extends FunctionalTestCase
{
    /**
     * @return iterable<string,array{string,int}>
     */
    public static function hostProvider(): iterable
    {
        yield 'the app' => ['repman.wip', 200];
        yield 'the app, any case and a trailing dot' => ['Repman.WIP.', 200];
        yield 'the proxy' => ['repo.repman.wip', 200];
        yield 'an organization' => ['buddy.repo.repman.wip', 200];
        yield 'an unknown organization' => ['nobody.repo.repman.wip', 403];
        yield 'two levels below the proxy' => ['x.buddy.repo.repman.wip', 403];
        yield 'another subdomain of the app' => ['www.repman.wip', 403];
        yield 'another domain' => ['example.com', 403];
        yield 'nothing' => ['', 403];
    }

    #[DataProvider('hostProvider')]
    public function testAnswersWhetherACertificateMayBeIssued(string $host, int $status): void
    {
        $this->fixtures->createOrganization('buddy', $this->fixtures->createAdmin('test@buddy.works', 'password'));

        // no session, no login: the web server asks this before any TLS connection to the host exists
        $this->client->request('GET', '/_tls/allowed', ['domain' => $host], [], ['HTTP_HOST' => '127.0.0.1:9180']);

        self::assertSame($status, $this->client->getResponse()->getStatusCode());
        self::assertFalse($this->client->getResponse()->headers->has('Set-Cookie'));
    }
}
