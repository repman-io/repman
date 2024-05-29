<?php

use Buddy\Repman\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ?? $_ENV['APP_URL_SCHEME'];
    switch ($_ENV['TRUSTED_HEADERSET']){
        case 'AWS_ELB':
            $trustedHeaderSet = Request::HEADER_X_FORWARDED_AWS_ELB;
            break;
        case 'TRAEFIK':
            $trustedHeaderSet = Request::HEADER_X_FORWARDED_TRAEFIK;
            break;
        case 'ALL':
        default:
            $trustedHeaderSet = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO;
    }
    Request::setTrustedProxies(explode(',', $trustedProxies), $trustedHeaderSet);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
