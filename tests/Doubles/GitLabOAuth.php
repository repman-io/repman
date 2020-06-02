<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use GuzzleHttp\Psr7\Response;
use Psr\Container\ContainerInterface;

final class GitLabOAuth
{
    public static function mockTokenAndUserResponse(string $email, ContainerInterface $container): void
    {
        $container->get(HttpClientStub::class)->setNextResponses([new Response(200, [], '{
          "access_token": "1f0af717251950dbd4d73154fdf0a474a5c5119adad999683f5b450c460726aa",
          "token_type": "bearer",
          "expires_in": 7200
        }'), new Response(200, [], self::getUserJson($email))]);
    }

    public static function mockUserResponse(string $email, ContainerInterface $container): void
    {
        $container->get(HttpClientStub::class)->setNextResponses([new Response(200, [], self::getUserJson($email))]);
    }

    private static function getUserJson(string $email): string
    {
        return '{
          "id": 1,
          "username": "john_smith",
          "email": "'.$email.'",
          "name": "John Smith",
          "state": "active",
          "avatar_url": "http://localhost:3000/uploads/user/avatar/1/index.jpg",
          "web_url": "http://localhost:3000/john_smith",
          "created_at": "2012-05-23T08:00:58Z",
          "bio": null,
          "location": null,
          "public_email": "john@example.com",
          "skype": "",
          "linkedin": "",
          "twitter": "",
          "website_url": "",
          "organization": "",
          "last_sign_in_at": "2012-06-01T11:41:01Z",
          "confirmed_at": "2012-05-23T09:05:22Z",
          "theme_id": 1,
          "last_activity_on": "2012-05-23",
          "color_scheme_id": 2,
          "projects_limit": 100,
          "current_sign_in_at": "2012-06-02T06:36:55Z",
          "identities": [
            {"provider": "github", "extern_uid": "2435223452345"},
            {"provider": "bitbucket", "extern_uid": "john_smith"},
            {"provider": "google_oauth2", "extern_uid": "8776128412476123468721346"}
          ],
          "can_create_group": true,
          "can_create_project": true,
          "two_factor_enabled": true,
          "external": false,
          "private_profile": false
        }';
    }
}
