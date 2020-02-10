<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RepoControllerTest extends FunctionalTestCase
{
    public function testAuthRequiredForOrganizationRepo(): void
    {
        $this->client->request('GET', $this->urlTo('repo_packages'));

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testPackagesActionWithInvalidToken(): void
    {
        $this->client->request('GET', $this->urlTo('repo_packages'), [], [], [
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPackagesAction(): void
    {
        $adminId = $this->createAndLoginAdmin('test@buddy.works', 'secret');

        $this->fixtures->createToken(
            $this->fixtures->createOrganization(
                'buddy',
                $adminId
            ),
            'secret-org-token'
        );

        $this->client->request('GET', 'repo/packages.json', [], [], [
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertMatchesPattern('
        {
            "notify-batch": "https://packagist.org/downloads/",
            "providers-url": "/p/%package%$%hash%.json",
            "metadata-url": "/p2/%package%.json",
            "search": "https://packagist.org/search.json?q=%query%&type=%type%",
            "mirrors": [
                {
                    "dist-url": "@string@.isUrl()",
                    "preferred": true
                }
            ],
            "providers-lazy-url": "/p/%package%"
        }
        ', $this->lastResponseBody());

        // check if last update date is changed
        $this->client->request('GET', $this->urlTo('organization_tokens', ['organization' => 'buddy']));
        self::assertStringNotContainsString('never', $this->lastResponseBody());
    }
}
