<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Service\ServedHosts;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Asked by the web server (Caddy's on-demand TLS) before it requests a certificate for a host name,
 * so certificates are only issued for hosts Repman serves; without it anyone pointing a name at the
 * server could use up the Let's Encrypt rate limits. The web server only serves this path on an
 * internal port.
 */
final class TlsController
{
    public function __construct(private ServedHosts $hosts)
    {
    }

    #[Route(path: '/_tls/allowed', name: 'tls_allowed', methods: ['GET'])]
    public function allowed(Request $request): Response
    {
        return new Response('', $this->hosts->serves($request->query->getString('domain')) ? Response::HTTP_OK : Response::HTTP_FORBIDDEN);
    }
}
