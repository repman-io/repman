<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RepoControllerTest extends FunctionalTestCase
{
    public function testAuthRequiredForOrganizationRepo(): void
    {
        $this->client->request('GET', '/packages.json', [], [], ['HTTP_HOST' => 'buddy.repo.repman.wip']);

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testPackagesActionWithInvalidToken(): void
    {
        $this->client->request('GET', '/packages.json', [], [], [
            'HTTP_HOST' => 'buddy.repo.repman.wip',
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testNotAllowedToSeeOtherOrganizationPackages(): void
    {
        $this->fixtures->createOrganization(
            'evil',
            $this->fixtures->createUser('bad@evil.corp')
        );

        $this->fixtures->createToken(
            $this->fixtures->createOrganization(
                'buddy',
                $this->fixtures->createUser()
            ),
            'secret-org-token'
        );

        $this->client->request('GET', '/packages.json', [], [], [
            'HTTP_HOST' => 'evil.repo.repman.wip',
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testOrganizationPackagesAction(): void
    {
        // create and login admin to check token last used date is changed
        $adminId = $this->createAndLoginAdmin('test@buddy.works', 'secret');

        $this->fixtures->createToken(
            $this->fixtures->createOrganization(
                'buddy',
                $adminId
            ),
            'secret-org-token'
        );

        // check token was never used
        $this->client->request('GET', $this->urlTo('organization_tokens', ['organization' => 'buddy']));
        self::assertStringContainsString('never', $this->lastResponseBody());

        $this->client->request('GET', '/packages.json', [], [], [
            'HTTP_HOST' => 'buddy.repo.repman.wip',
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertMatchesPattern('
        {
            "notify-batch": "https://packagist.org/downloads/",
            "providers-url": "http://repo.repman.wip/p/%package%$%hash%.json",
            "metadata-url": "http://repo.repman.wip/p2/%package%.json",
            "search": "https://packagist.org/search.json?q=%query%&type=%type%",
            "mirrors": [
                {
                    "dist-url": "http://repo.repman.wip/dists/%package%/%version%/%reference%.%type%",
                    "preferred": true
                }
            ],
            "providers-lazy-url": "http://repo.repman.wip/p/%package%"
        }
        ', $this->lastResponseBody());

        // check if last update date is changed
        $this->client->request('GET', $this->urlTo('organization_tokens', ['organization' => 'buddy']));
        self::assertStringNotContainsString('never', $this->lastResponseBody());
    }
}
