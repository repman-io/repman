<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Proxy;

use Buddy\Repman\Message\Proxy\AddDownloads;
use Buddy\Repman\Message\Proxy\AddDownloads\Package;
use Buddy\Repman\MessageHandler\Proxy\AddDownloadsHandler;
use Buddy\Repman\Query\Admin\Proxy\DownloadsQuery\DbalDownloadsQuery;
use Buddy\Repman\Query\Admin\Proxy\Model\Package as DownloadPackage;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class AddDownloadsHandlerTest extends IntegrationTestCase
{
    public function testAddDownloads(): void
    {
        /** @var AddDownloadsHandler $handler */
        $handler = $this->container()->get(AddDownloadsHandler::class);
        $handler->__invoke(new AddDownloads(
            [
                new Package('buddy-works/oauth2-client', '0.1.2'),
                new Package('buddy-works/oauth2-client', '0.1.2'),
                new Package('subctrine/dbal', '1.2.3'),
            ],
            $date = new \DateTimeImmutable(),
            '156.101.44.101',
            'Repman 1.0'
        ));

        $this->container()->get('doctrine.orm.entity_manager')->flush();

        $packages = $this
            ->container()
            ->get(DbalDownloadsQuery::class)
            ->findByNames(['buddy-works/oauth2-client', 'subctrine/dbal']);

        self::assertEquals([
            'buddy-works/oauth2-client' => new DownloadPackage(2, new \DateTimeImmutable($date->format('Y-m-d H:i:s'))),
            'subctrine/dbal' => new DownloadPackage(1, new \DateTimeImmutable($date->format('Y-m-d H:i:s'))),
        ], $packages);
    }
}
