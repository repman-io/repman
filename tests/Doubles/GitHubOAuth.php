<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Integration\GitHubApi;
use GuzzleHttp\Psr7\Response;
use Psr\Container\ContainerInterface;

final class GitHubOAuth
{
    public static function mockTokenResponse(string $email, ContainerInterface $container): void
    {
        $container->get(GitHubApi::class)->setPrimaryEmail($email);
        $container->get(HttpClientStub::class)->setNextResponses([new Response(200, [], '{"access_token":"e72e16c7e42f292c6912e7710c838347ae178b4a", "scope":"user:email", "token_type":"bearer"}')]);
    }
}
