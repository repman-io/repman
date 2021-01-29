<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Integration\BuddyApi;
use GuzzleHttp\Psr7\Response;
use Psr\Container\ContainerInterface;

final class BuddyOAuth
{
    public static function mockAccessTokenResponse(string $email, ContainerInterface $container): void
    {
        $container->get(BuddyApi::class)->setPrimaryEmail($email);
        $container->get(HttpClientStub::class)->setNextResponses([new Response(200, [], '{"access_token":"e72e16c7e42f292c6912e7710c838347ae178b4a"}')]);
    }
}
