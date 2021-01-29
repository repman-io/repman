<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Integration\BitbucketApi;
use GuzzleHttp\Psr7\Response;
use Psr\Container\ContainerInterface;

final class BitbucketOAuth
{
    public static function mockAccessTokenResponse(string $email, ContainerInterface $container): void
    {
        $container->get(BitbucketApi::class)->setPrimaryEmail($email);
        $container->get(HttpClientStub::class)->setNextResponses([new Response(200, [], '{"access_token":"e72e16c7e42f292c6912e7710c838347ae178b4a", "scope":"email", "token_type":"bearer"}')]);
    }

    public static function mockInvalidAccessTokenResponse(string $errorMessage, ContainerInterface $container): void
    {
        $container->get(HttpClientStub::class)->setNextResponses([new Response(500, [], '{"error_description": "'.$errorMessage.'"}')]);
    }

    public static function mockRefreshTokenResponse(string $newAccessToken, ContainerInterface $container): void
    {
        $container->get(HttpClientStub::class)->setNextResponses([new Response(200, [], '{"access_token":"'.$newAccessToken.'", "scope":"email", "token_type":"bearer"}')]);
    }
}
